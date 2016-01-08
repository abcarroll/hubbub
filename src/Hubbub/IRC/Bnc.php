<?php
/*
 * This file is a part of Hubbub, available at:
 * http://github.com/abcarroll/hubbub
 *
 * Copyright (c) 2015, A.B. Carroll <ben@hl9.net>
 * Hubbub is distributed under a BSD-like license.
 *
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code, or available at the URL above.
 */


namespace Hubbub\IRC;

/**
 * The BNC server, a simple IRC server that acts as a translation module between it's clients and other protocol client modules.
 * @package Hubbub\IRC
 */
class Bnc implements \Hubbub\Protocol\Server, \Hubbub\Iterable {
    use Parser;

    /**
     * @var \Hubbub\Net\Server
     */
    protected $net;

    /**
     * @var \Hubbub\Configuration
     */
    protected $conf;

    /**
     * @var \Hubbub\Logger
     */
    protected $logger;

    /**
     * @var \Hubbub\MessageBus
     */
    protected $bus;

    /**
     * All of our clients, as id => stdClass.
     *
     * This was originally implemented properly, but something just has a smell to it the way it originally was implemented.  Until further review, this will
     * have to do.
     * @var array
     */
    protected $clients = [];

    protected $networks = [];

    const REGISTRATION_TIMEOUT = 20;
    const MAX_PASSWORD_ATTEMPTS = 2;

    public function __construct(\Hubbub\Net\Server $net, \Hubbub\Configuration $conf, \Hubbub\Logger $logger, \Hubbub\MessageBus $bus, $name) {
        $this->conf = $conf;
        $this->logger = $logger;
        $this->bus = $bus;

        $this->net = $net;
        $this->net->setProtocol($this);

        $this->bus->subscribe([$this, 'busMessageHandler'], [
            'protocol' => 'irc'
        ]);

        $listen = $this->conf->get('irc/bnc/listen');
        $this->net->server('tcp://' . $listen);
    }

    public function busMessageHandler($msg) {
        if($msg['protocol'] == 'irc') {
            // group subscribe, group join, i_join, i_subscribe, ... etc
            /*if($message->action == 'group_subscribe') {

            }*/
        }
    }

    /**
     * @param int $clientId The network client identifier
     *
     * @return \Hubbub\IRC\BncClient
     */
    protected function getClient($clientId) {
        return $this->clients[$clientId];
    }

    public function on_client_connect($clientId) {
        $newClient = new BncClient($this->net, $this->logger, $clientId);;
        $this->clients[$clientId] = $newClient;

        $newClient->sendNotice("*", "*** You are connected...");
        $newClient->sendNotice("*", "*** Not looking up your hostname");
        $newClient->sendNotice("*", "*** Not checking ident");
    }

    public function on_client_disconnect($clientId) {
        unset($this->clients[$clientId]);
    }

    // When any data is received by a client ..
    public function on_client_recv($clientId, $data) {
        $this->logger->debug("Received data from clientId #$clientId: $data");

        $commands = explode("\n", $data);
        if(!empty($commands[count($commands) - 1])) {
            $incomplete = $commands[count($commands) - 1];
            $commands[count($commands) - 1] = '';
            // TODO Use proper logger
            trigger_error("Received incomplete command '$incomplete' - discarding", E_USER_WARNING);
        }

        foreach($commands as $line) {
            if(!empty($line)) {
                $client = $this->getClient($clientId);
                $this->on_recv_irc($client, $line);
            }
        }
    }

    public function on_recv_irc($client, $line) {
        $line = $this->parseIrcCommand($line);

        $try_method = 'recv_' . strtolower($line->cmd);
        $this->logger->debug("Trying method: $try_method");

        if(method_exists($this, $try_method)) {
            $this->$try_method($client, $line);
        }
    }

    public function on_client_send($client, $data) {
        // TODO: does this do anything?
        $this->logger->debug("BNC::on_client_send: " . $data);
    }

    public function iterate() {
        $this->net->iterate();
        //$this->logger->debug("BNC Server was iterated with " . count($this->clients) . " clients");

        // check all clients
        /** @var \Hubbub\IRC\BncClient $client */
        foreach($this->clients as $client) {
            // Check state times for expiration
            $cState = $client->getState();
            if(($cState == 'preauth' || $cState == 'unregistered') && $client->getSecondsInState() > self::REGISTRATION_TIMEOUT) {
                if(self::REGISTRATION_TIMEOUT > 0 && $client->getSecondsInState() > self::REGISTRATION_TIMEOUT) {
                    if($cState == 'preauth') {
                        $client->sendNotice('*', "*** DISCONNECTED: Client was not registered in a satisfactory amount of time.");
                    } else {
                        $client->sendNotice('*', "*** DISCONNECTED: Log-in was not completed in a satisfactory amount of time.");
                    }
                    $client->disconnect();
                }
            }
        }
    }

    protected function recv_cap(BncClient $client, $line) {
        $client->send("CAP ACK");
    }

    protected function recv_nick(BncClient $client, $line) {
        $client->nick = $line->args[0];
        $this->tryRegistration($client);
    }

    protected function recv_user(BncClient $client, $line) {
        $client->user = $line->args[0];
        $client->realName = $line->args[3];
        $this->tryRegistration($client);
    }

    protected function tryRegistration(BncClient $client) {
        $this->logger->debug("Trying registration...");

        if(!empty($client->nick) && !empty($client->user)) {
            $this->logger->debug("Pre-auth was completed with NICK: " . $client->nick . ", USER: " . $client->user . ", NAME: " . $client->realName);

            $password = $this->conf->get('irc/bnc/require-pass');
            if(!empty($password)) {
                $client->setState('unregistered');
                $client->sendNotice("*", "*** I'm going to have to ask to see your ID, " . $client->nick);
                $client->sendNotice("*", "*** Type /QUOTE PASS (your password) now.");
            } else {
                $this->finishRegistration($client);
            }
        } else {
            $this->logger->debug("Registration failed: Not enough data (this is normal)");
        }
    }

    protected function finishRegistration(BncClient $client, $givenPassword = null) {
        $authPassed = true;

        $compare = [
            'require-nick' => $client->nick,
            'require-user' => $client->user,
            'require-name' => $client->realName,
            'require-pass' => $givenPassword,
        ];

        $authMethods = 0;
        foreach($compare as $confName => $givenValue) {
            $confValue = $this->conf->get('irc/bnc/' . $confName);
            if($confValue !== null) {
                if($confValue !== $givenValue) {
                    $this->logger->debug("Failing loggin: $confName was set, $confValue !== $givenValue");
                    $authPassed = false;
                    break;
                }
                $authMethods++;
            }
        }

        if($authMethods > 0 || $this->conf->get('irc/bnc/no-authentication') === true) {
            if($authPassed) {
                $client->setState('registered');
                $this->welcome($client);
            } else {
                $client->sendNotice("*", "*** DISCONNECTED: One or more of your login parameters were incorrect.  Please try again later. ");
                $client->disconnect();
            }
        } else {
            $client->sendNotice("*", "*** DISCONNECTED: Please configure at least one authentication method. ");
            $client->disconnect();
        }
    }

    protected function recv_pass(BncClient $client, $line) {
        if($client->getState() != 'unregistered') {
            $client->sendNotice("*", " *** DISCONNECTED: Your client must register properly before attempting to send a password.");
            $client->disconnect();
        } else {
            $this->finishRegistration($client, $line->args[0]);
        }
    }

    protected function welcome(BncClient $client) {
        $client->setState('registered');

        $client->send(":Hubbub 001 {$client->nick} WELCOME");
        $motdFile = $this->conf->get('irc/bnc/motd-file');
        if(is_readable($motdFile)) {
            $f = file($this->conf->get('irc/bnc/motd-file'));
        } else {
            $f = ["The config value irc/bnc/motd_file was unreadable"];
        }

        $client->send(":Hubbub 001 {$client->nick} : Welcome to Hubbub's Internet Relay Chat Proxy, " . $client->nick);
        $client->send(":Hubbub 375 {$client->nick} : MOTD AS FOLLOWS");
        foreach($f as $line) {
            $client->send(":Hubbub 372 {$client->nick} : - " . trim($line));
        }
        $client->send(":Hubbub 375 {$client->nick} :END OF MOTD");

        //$this->send(":-Hubbub!Hubbub@Hubbub. PRIVMSG {$this->nick} :Welcome back, cowboy!");

        $selfMask = $client->nick . "!" . $client->user . '@localhost';

        // This is just some junk ... "examples" if you will
        $messages = [
            'Welcome back, cowboy!',
            'This is your console.  You may type any Hubbub console messages here and they will be relayed to the console module.',
            'You are currently connected to %NETWORKS% networks, and subscribed to %ALLCHAN% channels.',
            'If you want to listen in to some of those channels, tell me: subscribe #channel.network',
            'For a list of networks and their suffixes, type "network list"',
            'To hide chnanel, either PART it or type "hide console"',
        ];

        $client->send(":$selfMask JOIN :&hubbub");
        $client->send(":Hubbub 353 " . $client->nick . " = &hubbub :@-Hubbub +" . $client->nick);
        $client->send(":Hubbub TOPIC &hubbub :Hubbub Console Channel");

        foreach($messages as $m) {
            $client->send(":-Hubbub!Hubbub@Hubbub. PRIVMSG &hubbub :$m");
        }
        //$this->sendJoin("#hubbub");
    }

}
