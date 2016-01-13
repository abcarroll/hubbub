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

use Hubbub\DelimitedDataBuffer;
use Hubbub\TimerList;
use Hubbub\Utility;

/**
 * The BNC server, a simple IRC server that acts as a translation module between it's clients and other protocol client modules.
 * @package Hubbub\IRC
 */
class Bnc implements \Hubbub\Protocol\Server, \Hubbub\Iterable {

    const SELF_DO_NOT_DO_THIS_IF_DOING_SO_WILL_ADVANCE_US_TO_THE_LEFT = 4;

    use Parser;

    protected $name;
    protected $state = 'not-listening';

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
     * @var \Hubbub\TimerList
     */
    protected $timers;

    /**
     * All of our clients, as id => stdClass.
     *
     * This was originally implemented properly, but something just has a smell to it the way it originally was implemented.  Until further review, this will
     * have to do.
     * @var array
     */
    protected $clients = [];

    protected $clientBuffers = [];

    protected $networks = [];

    const REGISTRATION_TIMEOUT = 20;
    const MAX_PASSWORD_ATTEMPTS = 2;

    public function __construct(\Hubbub\Net\Server $net, \Hubbub\Configuration $conf, \Hubbub\Logger $logger, \Hubbub\MessageBus $bus, $name) {
        $this->conf = $conf;
        $this->logger = $logger;
        $this->bus = $bus;
        $this->name = $name;

        $this->net = $net;
        $this->net->setProtocol($this);

        $this->timers = new TimerList();

        $this->bus->subscribe([$this, 'busMessageHandler'], [
            'protocol' => 'irc'
        ]);

        $this->createListener();
    }

    public function createListener() {
        $location = $this->conf->get('irc/bnc/listen');
        $result = $this->net->server('tcp://' . $location);
        if(!$result) {
            $this->logger->info("BNC " . $this->name . " failed to listen at $location.  Retrying in 30 seconds...");
            $this->state = 'not-listening';
            $this->timers->addBySeconds([$this, 'createListener'], 30, 'createListener');
        } else {
            $this->logger->info("BNC " . $this->name . " created: $location");
            $this->state = 'listening';
        }
        //exit;
    }


    public function busMessageHandler($busMsg) {
        // Send bus messages to an additional method for handling
        if($busMsg['protocol'] == 'irc' && !empty($busMsg['action'])) {
            if(method_exists($this, 'onBus' . $busMsg['action'])) {
                $this->{'onBus' . $busMsg['action']}($busMsg);
            }
        }
    }

    protected function onBusCreate($busMsg) {
        $name = $busMsg['network'];
        $this->networks[$name] = [
            'name'     => $name,
            'channels' => [],
        ];
    }

    protected function onBusSubscribe($busMsg) {
        $network = $busMsg['network'];
        $channel = $busMsg['channel'];
        $this->networks[$network]['channels'][$channel] = [
            'name'        => $channel,
            'joinedSince' => time(),
            'topic'       => [],
            'modes'       => [],
            'nameList'    => [],
        ];
    }

    protected function onBusNameList($busMsg) {
        if($busMsg['action'] == 'nameList') {
            $network = $busMsg['network'];
            $this->networks[$network]['channels'][$busMsg['channel']]['nameList'] = $busMsg['nameList'];
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
        // TODO don't use new operator
        $newClient = new BncClient($this->net, $this->logger, $clientId);
        $this->clients[$clientId] = $newClient;
        $this->clientBuffers[$clientId] = new DelimitedDataBuffer("\r\n");

        $this->timers->addBySeconds(function () use ($newClient) {
            $newClient->sendNotice('*', "*** DISCONNECTED: Client was not registered in a satisfactory amount of time.");
            $newClient->disconnect();
        }, 30, "registration-timeout:$clientId");

        /*$newClient->sendNotice("*", "*** You are connected...");
        $newClient->sendNotice("*", "*** Not looking up your hostname");
        $newClient->sendNotice("*", "*** Not checking ident");*/
    }

    public function on_client_disconnect($clientId) {
        unset($this->clients[$clientId]);
    }

    // When any data is received by a client ..
    public function on_client_recv($clientId, $data) {
        $this->logger->debug("Received data from clientId #$clientId: $data");

        /** @var DelimitedDataBuffer $buffer */
        $buffer = $this->clientBuffers[$clientId];
        $buffer->receive($data);

        foreach($buffer->consumeAll() as $line) {
            $client = $this->getClient($clientId);
            $this->on_recv_irc($client, $line);
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
        $this->timers->checkTimers();
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
                        ;
                    } else {
                        $client->sendNotice('*', "*** DISCONNECTED: Log-in was not completed in a satisfactory amount of time.");
                    }
                    $client->disconnect();
                }
            }
        }
    }

    /*protected function recv_cap(BncClient $client, $line) {
        $client->send("CAP ACK");
        return $line;
    }*/

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
                $authMethods++;
                if($confValue !== $givenValue) {
                    $this->logger->debug("Failing loggin: $confName was set, $confValue !== $givenValue");
                    $authPassed = false;
                    break;
                }
            }
        }

        if($authMethods > 0 || $this->conf->get('irc/bnc/no-authentication') === true) {
            if($authPassed) {
                $this->timers->remove("registration-timeout:" . $client->clientId);
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
        $this->pass = $line->args[0];
        if($client->getState() == 'unregistered') {
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
        /*$messages = [
            'Welcome back, cowboy!',
            'This is your console.  You may type any Hubbub console messages here and they will be relayed to the console module.',
            'You are currently connected to %NETWORKS% networks, and subscribed to %ALLCHAN% channels.',
            'If you want to listen in to some of those channels, tell me: subscribe #channel.network',
            'For a list of networks and their suffixes, type "network list"',
            'To hide chnanel, either PART it or type "hide console"',
        ];*/

        var_dump($this->networks);

        $messages = [
            Utility::varDump($this->networks)
        ];

        $client->send(":$selfMask JOIN :&hubbub");
        $client->send(":Hubbub 353 " . $client->nick . " = &hubbub :@-Hubbub +" . $client->nick);
        $client->send(":Hubbub TOPIC &hubbub :Hubbub Console Channel");

        foreach($messages as $m) {
            $client->send(":-Hubbub!Hubbub@Hubbub. PRIVMSG &hubbub :$m");
        }
        //$this->sendJoin("#hubbub");

        foreach($this->networks as $network) {
            $networkName = $network['name'];
            foreach($network['channels'] as $channel) {
                $channelName = $channel['name'] . '.' . $networkName;
                $client->send(":$selfMask JOIN :$channelName");
                $client->send(":Hubbub 353 " . $client->nick . " = $channelName :@Hubbub-Z +" . $client->nick);
                //$client->send(":Hubbub TOPIC $channelName :" . $channel['topic']);
            }
        }

    }

}
