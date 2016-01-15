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

    protected $componentName;
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

    public function __construct(\Hubbub\Net\Server $net, \Hubbub\Configuration $conf, \Hubbub\Logger $logger, \Hubbub\MessageBus $bus, $componentName) {
        $this->conf = $conf;
        $this->logger = $logger;
        $this->bus = $bus;
        $this->componentName = $componentName;

        $this->net = $net;
        $this->net->setProtocol($this);

        $this->timers = new TimerList();

        $this->bus->subscribe([$this, 'onBus'], [
            'protocol' => 'irc'
        ]);

        $this->createListener();
    }

    protected function onRecv_cap(BncClient $client, $line) {
        $client->send("CAP ACK");
        return $line;
    }


    protected function onRecv_ping(BncClient $client, $line) {
        $client->send(":" . $this->myHost . " PONG " . $this->myHost . " :" . $line->args[0]);
    }

    protected function onRecv_pass(BncClient $client, $line) {
        $this->pass = $line->args[0];
        if($client->getState() == 'unregistered') {
            $this->finishRegistration($client, $line->args[0]);
        }
    }

    protected function onRecv_nick(BncClient $client, $line) {
        $client->nick = $line->args[0];
        $this->tryRegistration($client);
    }

    protected function onRecv_user(BncClient $client, $line) {
        $client->user = $line->args[0];
        $client->realName = $line->args[3];
        $this->tryRegistration($client);
    }

    protected function onRecv_version(BncClient $client, $line) {
        $versionInfo = [
            [351, $client->nick, "Hubbub: Running Hubbub [http://github.com/abcarroll/github].  World's best (only?) php powered irc bnc, client, and bot."].
            [351, $client->nick, "PHP: Hubbub is running on PHP/" . phpversion()]
        ];

        foreach($versionInfo as $versionParam) {
            $client->sendFrom($this->myHost, implode(' ', $versionParam));
        }
    }

    protected function onRecv_privmsg(BncClient $client, $line) {
        $bncChannel = $line->args[0];
        if(strpos($bncChannel, '.') !== false) {
            list($channel, $network) = Utility::explodeRev('.', $bncChannel, 2);
            $this->bus->publish([
                'protocol' => 'irc',
                'action'   => 'msg',
                'network'  => $network,
                'to'       => $channel,
                'message'  => $line->privmsg->msg
            ]);
        }

        if($line->args[0] == $this->consoleChan) {
            foreach($this->clients as $c) {
                if($c !== $client) {
                    $client->sendArray(['PRIVMSG', $this->consoleChan, ':' . $line->privmsg->msg], $client->hostmask);
                }
            }
        }
    }

    protected function sendNamesList(BncClient $client, $channelName, $nameList) {
        // TODO fix to send as few lines as possible
        foreach($nameList as $name) {
            $client->send(":" . $this->myHost . " 353 " . $client->nick . " = $channelName :$name");
        }
        $client->send(":" . $this->myHost . " 366 " . $client->nick . " $channelName :End of /NAMES list.");
    }

    /**
     * Checks to see if we have enough information received from the client to complete initial registration.
     *
     * @param BncClient $client
     */
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


    protected function welcome(BncClient $client) {
        $client->setState('registered');
        $selfMask = $client->nick . "!" . $client->user . '@hubbub.localnet';
        $client->hostmask = $selfMask;

        // TODO: this *should* use constants .. eg. Codes::RPL_WELCOME instead of 001...
        // You can do $this->commandToNumeric('RPL_WELCOME'); but that's quite the mou - ... finger -- err hand? full
        $serverWelcome = [
            ['001', $client->nick, ":Welcome to the Hubbub BNC Internet Relay Chat Server " . $client->nick],
            ['002', $client->nick, ":Your host is " . $this->myHost . " running Hubbub/HEAD"],
            ['003', $client->nick, ":This server was created " . date('r', $_SERVER['REQUEST_TIME'])],

            // RPL_MYINFO:
            // <server_name> <version> <user_modes> <chan_modes> <channel_modes_with_params> <user_modes_with_params> <server_modes> <server_modes_with_params>
            // But we just send some gibberish..
            ['004', $client->nick, ":" . $this->myHost . " Hubbub abBcCFioqrRswx abehiIklmMnoOPqQrRstvVz"],

            // RPL_ISUPPORT todo: Improve
            ['005', $client->nick,
                ":" .
                 "RFC2818 " .
                 "NETWORK=Hubbub-BNC " .
                 "IRCD=Hubbub " .
                 "CHARSET=UTF-8 " .
                 "CASEMAPPING=ascii " .
                 "PREFIX=(uOqaohv).@~@@%+ " . "CHANTYPES=&#!+.~ " .
                 "CHANMODES=beI,k,l,imMnOPQRstVz CHANLIMIT=#&+:10 :are supported on this server"
            ],

            ['005', $client->nick,
                ":CHANNELLEN=50 NICKLEN=4 TOPICLEN=490 AWAYLEN=127 KICKLEN=400 MODES=5 MAXLIST=beI:50 EXCEPTS=e INVEX=I PENALTY :are supported on this server"
            ],

            ['251', $client->nick, ":There are " . count($this->clients) . " users and 1 services on " . count($this->networks) . " servers"],
            ['254', $client->nick, count($this->channels), ":channels formed"],
            ['255', $client->nick, ":I have " . count($this->clients) . " users, 1 services and 0 servers"],
        ];

        foreach($serverWelcome as $line) {
            $client->sendArray($line, $this->myHost);
        }

        $motdFile = $this->conf->get('irc/bnc/motd-file');
        if(is_readable($motdFile)) {
            $f = file($motdFile);
            $client->sendArray(['375', $client->nick, ":MOTD AS FOLLOWS"], $this->myHost);
            foreach($f as $line) {
                $client->sendArray(['372', $client->nick, ": - " . trim($line)], $this->myHost);
            }
            $client->sendArray(['376', $client->nick, ":END OF MOTD"], $this->myHost);
        } else {
            $client->sendArray(['422', $client->nick, ": The config value irc/bnc/motd_file was unreadable"], $this->myHost);
        }

        // Send our 'hostname'
        $client->sendArray(['396', $client->nick, $selfMask, ': is your displayed hostname now']);

        // Force-Join into the console channel; todo go by setting...
        $client->sendArray(['JOIN', ':' . $this->consoleChan], $selfMask);

        // Server sends topic for console channel
        $goofyTopics = [
            'Hubbub Central Command Station', 'Hubbub welcomes you to the 340th annual testing of the BNC', 'Just another lonely channel...',
            'This isn\'t your everyday channel, mister...'
        ];
        $client->sendArray(['TOPIC', $this->consoleChan, ':' . $goofyTopics[array_rand($goofyTopics)]], $this->myHost);

        // Notify other connected client of the newly connected client JOIN'ing the console channel while also building a list of names for the RPL_NAMREPLY
        $localClientList = ['.-Hubbub'];
        /** @var BncClient $c */
        foreach($this->clients as $c) {
            $localClientList[] = $c->nick;
            $c->sendArray(['JOIN', $this->consoleChan], $selfMask);
        }

        // Send the RPL_NAMREPLY to the newly connected client
        $this->sendNamesList($client, $this->consoleChan, $localClientList);

        // Here we can send some 'secret' messages only to the newly connected client in the console channel
        /*$consoleMessages = [
            Utility::varDump($this->networks)
        ];
        foreach($consoleMessages as $m) {
            $client->sendMsg($this->consoleMask, $this->consoleChan, $m);
        }*/

        // Force-join the newly connected client to the bnc bridge channels
        foreach($this->networks as $network) {
            $networkName = $network['name'];
            foreach($network['channels'] as $channel) {
                $channelName = $channel['name'] . '.' . $networkName;
                $client->sendArray(['JOIN', ':' . $channelName], $selfMask);
                if(!empty($channel['topic'])) {
                    $client->sendArray(['TOPIC', $channelName, ':' . $channel['topic']]);
                }
                // todo: what should ultimately be done about this?
                // currently, we'll be shown twice (even twice wiht the same nick if our bnc nick and irc nick match).  the RFC states the RPL_NAMREPLY
                // "MUST" send the newly joined nickname along with the rest of the RPL_NAMREPLY..
                $channel['nameList'][] = '.' . $client->nick;
                $this->sendNamesList($client, $channelName, $channel['nameList']);
                $client->sendArray(['MODE', $channelName, '+a', $client->nick], $this->myHost);
            }
        }
    }

    /**
     * Callable for handling all incoming bus messages
     *
     * @param $busMsg
     */
    public function onBus($busMsg) {
        // Send bus messages to an additional method for handling
        if($busMsg['protocol'] == 'irc' && !empty($busMsg['action'])) {
            if(method_exists($this, 'onBus_' . $busMsg['action'])) {
                $this->{'onBus_' . $busMsg['action']}($busMsg);
            }
        }

        /** @var BncClient $c */
        foreach($this->clients as $c) {
            if(!empty($busMsg['protocol']) && !empty($busMsg['action']) &&  $busMsg['protocol'] == 'irc' && $busMsg['action'] == 'raw') {
                $c->sendMsg($this->consoleMask, $this->consoleChan, '[' . $busMsg['network'] . ' RAW] ' . $busMsg['raw']);
            } else {
                $c->sendMsg($this->consoleMask, $this->consoleChan, Utility::varDump($busMsg));
            }
        }

    }

    public function onBus_Raw($busMsg) {
        /** @var BncClient $c */
        foreach($this->clients as $c) {

        }
    }

    protected function onBus_create($busMsg) {
        $name = $busMsg['network'];
        $this->networks[$name] = [
            'name'     => $name,
            'channels' => [],
        ];
    }

    protected function onBus_subscribe($busMsg) {
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

    protected function onBus_unsubscribe($busMsg) {
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

    protected function onBus_topic($busMsg) {
        $network = $busMsg['network'];
        $channel = $busMsg['channel'];
        $this->networks[$network]['channels'][$channel]['topic'] = $busMsg['topic'];
    }

    protected function onBus_nameList($busMsg) {
        if($busMsg['action'] == 'nameList') {
            $network = $busMsg['network'];
            $this->networks[$network]['channels'][$busMsg['channel']]['nameList'] = $busMsg['nameList'];
        }
    }

    protected function onBus_join($msg) {
        /** @var \Hubbub\IRC\BncClient $c */
        foreach($this->clients as $c) {
            $c->sendJoin($msg['channel']);
        }
    }

    protected function onBus_privmsg($msg) {
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

    /**
     * Creates a listening server
     */
    public function createListener() {
        $location = $this->conf->get('irc/bnc/listen');
        $result = $this->net->server('tcp://' . $location);
        if(!$result) {
            $this->logger->info("BNC " . $this->componentName . " failed to listen at $location.  Retrying in 30 seconds...");
            $this->state = 'not-listening';
            $this->timers->addBySeconds([$this, 'createListener'], 30, 'createListener');
        } else {
            $this->logger->info("BNC " . $this->componentName . " created: $location");
            $this->state = 'listening';
        }
    }

    /**
     * Callable for bnc component's iteration
     */
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

    /**
     * Callable for new client connections to the server socket
     *
     * @param int $clientId
     */
    public function onClientConnect($clientId) {
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

    /**
     * Callable for recently disconnected client connections to the server socket
     *
     * @param int $clientId
     */
    public function onClientDisconnect($clientId) {
        unset($this->clients[$clientId]);
    }

    /**
     * Callable
     *
     * @param int    $clientId
     * @param string $data
     *
     * @returns mixed
     */
    public function onClientRecv($clientId, $data) {
        /** @var DelimitedDataBuffer $buffer */
        $buffer = $this->clientBuffers[$clientId];
        $buffer->receive($data);

        foreach($buffer->consumeAll() as $line) {
            // Several very popular clients erroneously only send LF instead of CRLF as protocol delimiters
            if(substr($line, 0, -1) == "\r") {
                $line = substr($line, 0, -1);
            }

            $client = $this->getClient($clientId);

            // Handle IRC message
            $line = $this->parseIrcCommand($line);
            $try_method = 'onRecv_' . strtolower($line->cmd);
            if(method_exists($this, $try_method)) {
                $this->$try_method($client, $line);
            }

        }

        return $data; // TODO why does the interface define a return of mixed?
    }

    public function onClientSend($client, $data) {
        // TODO: does this do anything?
        $this->logger->debug("[BNC SEND] " . trim($data));
        /*
         * this causes a loop :)
         *//*$this->bus->publish([
            'protocol' => 'irc',
            'network'  => $this->componentName,
            'action'   => 'raw',
            'raw'      => trim($data),
        ]);*/
    }


}
