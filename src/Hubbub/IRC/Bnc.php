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


    /**
     * A lookup table for all channels, eg. '#foo.network' => &channelStructure
     * @var array
     */
    protected $channels = [];

    protected $myHost = 'irc.hubbub.localnet';
    protected $consoleMask = '-Hubbub!Hubbub@hubbub.localnet';
    protected $consoleChan = '&localnet';


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

        if(empty($busMsg['from'])) { // we joined the channel
            $network = $busMsg['network'];
            $channel = $busMsg['channel'];
            $this->networks[$network]['channels'][$channel] = [
                'name'        => $channel,
                'joinedSince' => time(),
                'topic'       => '',
                'modes'       => [],
                'nameList'    => [],
            ];
        } else { // another user joined
            $network = $busMsg['network'];
            $channel = $busMsg['channel'];
            $this->networks[$network]['channels'][$channel]['userList'][] = $busMsg['from']->nick;
            /** @var BncClient $c */
            foreach($this->clients as $c) {
                // instead of using raw .. use something better? TODO
                $c->send($busMsg['from']->raw . ' JOIN :' . $channel . '.' . $network);
            }
        }
    }

    protected function onBusUnsubscribe($busMsg) {
        // todo .. this is basically subscribe just with a few bytes difference
        if(empty($busMsg['from'])) { // we joined the channel
            // TODO: implement self-parting a channel
        } else { // another user joined
            $network = $busMsg['network'];
            $channel = $busMsg['channel'];
            // todo unset here
            //$this->networks[$network]['channels'][$channel]['userList'][] = $busMsg['from']->nick;
            /** @var BncClient $c */
            foreach($this->clients as $c) {
                // instead of using raw .. use something better? TODO
                $c->send($busMsg['from']->raw . ' PART :' . $channel . '.' . $network);
            }
        }
    }

    protected function onBusTopic($busMsg) {
        $network = $busMsg['network'];
        $channel = $busMsg['channel'];
        $this->networks[$network]['channels'][$channel]['topic'] = $busMsg['topic'];
    }

    protected function onBusNameList($busMsg) {
        if($busMsg['action'] == 'nameList') {
            $network = $busMsg['network'];
            $this->networks[$network]['channels'][$busMsg['channel']]['nameList'] = $busMsg['nameList'];
        }
    }

    protected function onBusJoin($msg) {
        /** @var \Hubbub\IRC\BncClient $c */
        foreach($this->clients as $c) {
            $c->sendJoin($msg['channel']);
        }
    }

    protected function onBusPrivmsg($msg) {
        /** @var \Hubbub\IRC\BncClient $c */
        foreach($this->clients as $c) {
            $c->send($msg['from'] . ' PRIVMSG ' . $msg['to']->raw . '.' . $msg['network'] . ' :' . $msg['message']);
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
        $this->clientBuffers[$clientId] = new DelimitedDataBuffer("\n");

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
        /** @var DelimitedDataBuffer $buffer */
        $buffer = $this->clientBuffers[$clientId];
        $buffer->receive($data);

        foreach($buffer->consumeAll() as $line) {
            // If the client correctly implements the IRC protocol -
            // m*RC does NOT send the CR!
            if(substr($line, 0, -1) == "\r") {
                $line = substr($line, 0, -1);
            }

            $client = $this->getClient($clientId);
            $this->on_recv_irc($client, $line);
        }
    }

    public function on_recv_irc($client, $line) {
        $line = $this->parseIrcCommand($line);

        $try_method = 'recv_' . strtolower($line->cmd);
        if(method_exists($this, $try_method)) {
            $this->$try_method($client, $line);
        }
    }

    public function on_client_send($client, $data) {
        // TODO: does this do anything?
        $this->logger->debug("[BNC SEND] " . trim($data));
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

    protected function recv_version(BncClient $client, $line) {

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
                    $this->logger->debug("Failing login: $confName was set, $confValue !== $givenValue");
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
                $client->sendNotice("*", "*** DISCONNECTED: Login incorrect.  Please try again later. ");
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

    protected function recv_ping(BncClient $client, $line) {
        $client->send(":" . $this->myHost . " PONG " . $this->myHost . " :" . $line->args[0]);
    }

    protected function recv_privmsg(BncClient $client, $line) {
        $bncChannel = $line->args[0];
        list($channel, $network) = Utility::explodeRev('.', $bncChannel, 2);
        $this->bus->publish([
            'protocol' => 'irc',
            'action'   => 'msg',
            'network'  => $network,
            'to'       => $channel,
            'message'  => $line->privmsg->msg
        ]);
    }

    protected function welcome(BncClient $client) {
        $client->setState('registered');

        $serverPrefix = ':' . $this->myHost;

        $client->send($serverPrefix . " 001 {$client->nick} :Welcome to the Hubbub BNC Internet Relay Chat Server " . $client->nick);
        $client->send($serverPrefix . " 002 {$client->nick} :Your host is " . $this->myHost . " running Hubbub/" . phpversion());
        $client->send($serverPrefix . " 003 {$client->nick} :This server was created " . date('r', $_SERVER['REQUEST_TIME']));

        // RPL_MYINFO:
        // <server_name> <version> <user_modes> <chan_modes> <channel_modes_with_params> <user_modes_with_params> <server_modes> <server_modes_with_params>
        // But we just send some gibberish..
        $client->send($serverPrefix . " 004 {$client->nick} :" . $this->myHost . ' Hubbub/PHP abBcCFioqrRswx abehiIklmMnoOPqQrRstvVz');

        // RPL_ISUPPORT
        $client->send($serverPrefix . " 005 {$client->nick} :" .
                      "RFC2818 " .
                      "NETWORK=Hubbub-BNC " .
                      "IRCD=Hubbub " .
                      "CHARSET=UTF-8 " .
                      "CASEMAPPING=ascii " .
                      "PREFIX=(uOqaohv).@~@@%+ " . "CHANTYPES=&#!+.~ " .
                      "CHANMODES=beI,k,l,imMnOPQRstVz CHANLIMIT=#&+:10 :are supported on this server");

        $client->send($serverPrefix . " 005 {$client->nick} :CHANNELLEN=50 NICKLEN=4 TOPICLEN=490 AWAYLEN=127 KICKLEN=400 MODES=5 MAXLIST=beI:50 EXCEPTS=e INVEX=I PENALTY :are supported on this server");

        //$client->send($serverPrefix . " 005 {$client->nick} :RFC2818 NETWORK=Hubbub-BNC IRCD=Hubbub CHARSET=UTF-8 CASEMAPPING=ascii PREFIX=(ov)@+ ");


        $client->send($serverPrefix . " 251 {$client->nick} :There are 2 users and 1 services on " . count($this->networks) . " servers");
        $client->send($serverPrefix . " 254 {$client->nick} 1 :channels formed");
        $client->send($serverPrefix . " 255 {$client->nick} :I have " . count($this->clients) . " users, 1 services and 0 servers");
        //$client->send($serverPrefix . " 266 {$client->nick} :I have 2 users, 1 services and 0 servers");
        //$client->send($serverPrefix . " 250 {$client->nick} :I have 2 users, 1 services and 0 servers");

        $motdFile = $this->conf->get('irc/bnc/motd-file');
        if(is_readable($motdFile)) {
            $f = file($this->conf->get('irc/bnc/motd-file'));
        } else {
            $f = ["The config value irc/bnc/motd_file was unreadable"];
        }

        $client->send(":" . $this->myHost . " 375 {$client->nick} : MOTD AS FOLLOWS");
        foreach($f as $line) {
            $client->send(":" . $this->myHost . " 372 {$client->nick} : - " . trim($line));
        }
        $client->send(":" . $this->myHost . " 376 {$client->nick} :END OF MOTD");

        $selfMask = $client->nick . "!" . $client->user . '@hubbub.localnet';

        $client->send(":" . $this->myHost . " 396 {$client->nick} $selfMask :is your displayed hostname now");

        //$this->send(":-Hubbub!Hubbub@Hubbub. PRIVMSG {$this->nick} :Welcome back, cowboy!");



        // This is just some junk ... "examples" if you will
        /*$messages = [
            'Welcome back, cowboy!',
            'This is your console.  You may type any Hubbub console messages here and they will be relayed to the console module.',
            'You are currently connected to %NETWORKS% networks, and subscribed to %ALLCHAN% channels.',
            'If you want to listen in to some of those channels, tell me: subscribe #channel.network',
            'For a list of networks and their suffixes, type "network list"',
            'To hide chnanel, either PART it or type "hide console"',
        ];*/

        $messages = [
            Utility::varDump($this->networks)
        ];


        $client->send(":$selfMask JOIN :" . $this->consoleChan);

        $localClientList = ['.-Hubbub'];
        /** @var BncClient $c */
        foreach($this->clients as $c) {
            $localClientList[] = $c->nick;
            $c->send(":$selfMask JOIN :" . $this->consoleChan); // notify other bnc clients of their join
        }
        $client->send(":" . $this->myHost . " TOPIC " . $this->consoleChan . " :Hubbub Console Channel!");
        $this->sendNamesList($client, $this->consoleChan, $localClientList);

        foreach($messages as $m) {
            $client->sendMsg($this->consoleMask, $this->consoleChan, $m);
        }
        //$this->sendJoin("#hubbub");

        foreach($this->networks as $network) {
            $networkName = $network['name'];
            foreach($network['channels'] as $channel) {
                $channelName = $channel['name'] . '.' . $networkName;
                $client->send(":$selfMask JOIN :$channelName");
                if(!empty($channel['topic'])) {
                    $client->send(":" . $this->myHost . " TOPIC $channelName :" . $channel['topic']);
                }
                $channel['nameList'][] = $client->nick; // we'll be shown twice since we must comply with the RFC and MUST show the local nickname in the channel
                $this->sendNamesList($client, $channelName, $channel['nameList']);
                $client->send(":" . $this->myHost . " MODE $channelName +a " . $client->nick);
            }
        }

    }

    protected function sendNamesList(BncClient $client, $channelName, $nameList) {
        // Crappy as hell - we can send multiple $names per entry..
        foreach($nameList as $name) {
            $client->send(":" . $this->myHost . " 353 " . $client->nick . " = $channelName :$name");
        }
        $client->send(":" . $this->myHost . " 366 " . $client->nick . " $channelName :End of /NAMES list.");
    }

}
