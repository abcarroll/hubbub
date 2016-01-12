<?php
/*
 * This file is a part of Hubbub, available at:
 * http://github.com/abcarroll/hubbub
 *
 * Copyright (c) 2013-2015, A.B. Carroll <ben@hl9.net>
 * Hubbub is distributed under a BSD-like license.
 *
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code, or available at the URL above.
 */

namespace Hubbub\IRC;
use Hubbub\Utility;
use stdClass;

/**
 * Class Client
 * @package Hubbub\IRC
 */
class Client implements \Hubbub\Protocol\Client, \Hubbub\Iterable {
    use Parser;
    use Senders;

    /**
     * @var \Hubbub\Net\Client
     */
    protected $net, $logger, $bus, $conf;
    protected $componentName;

    protected $timers;

    protected $protocol = 'irc';
    protected $state = 'disconnected';

    protected $recvBuffer = ''; // Where incomplete, fragmented commands go for temporary storage

    public $serverList = [];
    public $serverInfo = [];
    public $cfg = [
        'nickname' => 'Hubbub-Z',
        'username' => 'Hubbub',
        'realname' => 'Bob Marley',
    ];

    protected $currentServer, $currentPort;
    protected $currentServerIdx = 0;
    protected $nextAction;
    protected $nextActionTime = 0;

    /**
     * @var int
     * How many times we've tried to reconnect, as a way to introduce reconnection throttling.
     * This needs to be reset to zero at some point, beyond RPL_WELCOME, after the connection has been established for a significant amount of time.
     * That is, we're not just connecting, and being disconnected (kicked, banned, whatever) shortly after..
     */
    protected $reconnectAttempt = 0;

    protected $currentServerIpAddr;
    protected $waitingForHostResolve;
    protected $resolveStarted = 0;

    /**
     * The current nickname of the client
     * @var string
     */
    protected $nick;

    /**
     * The current 'username' of the client
     * @var string
     */
    protected $user;

    /**
     * The current 'real name' of the client
     * @var string
     */
    protected $name;

    /**
     * The current @host of the client
     * @var string
     */
    protected $myHost;

    protected $serverMotd = '';
    protected $channels = [];
    protected $modules = []; // sub-modules, this system is currently disabled



    public function __construct(\Hubbub\Net\Client $net, \Hubbub\Logger $logger, \Hubbub\MessageBus $bus, \Hubbub\Configuration $conf,
        \Hubbub\DelimitedDataBuffer $bufferQueue, \Hubbub\TimerList $timers, $componentName) {
        $this->net = $net;
        $this->logger = $logger;
        $this->bus = $bus;
        $this->conf = $conf;
        $this->componentName = $componentName;

        $this->delimitedBufferQueue = $bufferQueue;
        $this->delimitedBufferQueue->setDelimiter("\r\n");

        $this->timers = $timers;

        $this->bPublish([
            'protocol'  => 'irc',
            'action'    => 'create',
        ]);

        // Set the network's protocol to ths IRC object; the IRC object will receive event notifications via the network handler
        $this->net->setProtocol($this);

        // Setup our mbus subscription
        $this->bus->subscribe([$this, 'handleBusMessage']);

        $this->serverList = $this->conf->get($this->protocol . '/' . $componentName . '/serverList');
        $this->tryNext();
    }

    public function handleBusMessage($msg) {
        // Incoming DNS messages
        if(isset($msg['protocol']) && $msg['protocol'] == 'dns') {
            if(isset($msg['action']) && $msg['action'] == 'response' && $this->state == 'attempt-resolve' && $this->currentServer == $msg['host']) {
                if(!empty($msg['result'])) {
                    $result = $msg['result'][0];
                    $this->logger->info("Resolved to: " . $result);
                    $this->setState('pre-auth');
                    $this->currentServerIpAddr = $result;
                    $this->connectNext();
                } else {
                    $this->logger->notice("DNS lookup failed for hostname " . $this->currentServer . ": " . $msg['message']);
                }
            }
        }
    }

    protected function tryNext() {
        if(count($this->serverList) > 0) {
            $this->setState('attempt-resolve');
            $this->resolveStarted = time();
            $nextServer = $this->serverList[($this->currentServerIdx++ % count($this->serverList))];

            if(strpos($nextServer, ':') !== null) {
                list($host, $port) = explode(':', $nextServer);
                $this->currentServer = $host;
                $this->currentPort = $port;
            } else {
                $this->currentServer = $nextServer;
                $this->currentPort = 6667;
            }

            if(filter_var($this->currentServer, FILTER_VALIDATE_IP)) {
                // It's an IP address, no need to resolve the hostname...
                $this->setState('pre-auth');
                $this->currentServerIpAddr = $this->currentServer;
                $this->connectNext();
            } else {
                $this->logger->info("Attempting to resolve hostname: $nextServer");
                $this->bus->publish([
                    'protocol' => 'dns',
                    'action'   => 'resolve',
                    'host'     => $this->currentServer
                ]);
            }
        }
    }

    protected function connectNext() {
        $this->logger->info("Connecting to " . $this->currentServer . ':' . $this->currentPort . ' (' . $this->currentServerIpAddr . ')');
        $this->net->connect("tcp://" . $this->currentServerIpAddr . ':' . $this->currentPort);
    }

    /* --- Properly set states on connect and disconnect --- */
    public function on_connect($socket = null) {
        $this->sendUser($this->cfg['username'], $this->cfg['realname']);
        $this->sendNick($this->cfg['nickname']);
        $this->nick = $this->cfg['nickname'];
        $this->setState('gave-auth');
    }

    public function send($data) {
        file_put_contents("log/raw-protocol.log", " > $data\n", FILE_APPEND);
        $this->logger->debug("RAW > $data");

        return $this->net->send("$data\n");
    }


    protected function setState($state) {
        $this->state = $state;

        $this->bus->publish([
            'protocol' => 'irc',
            'network' => $this->componentName,
            'action' => 'state-change',
            'state' => $state,
        ]);
    }

    public function on_disconnect() {
        $this->setState('disconnected');
        $this->nextAction = 'connect';

        $connectionDelay = (pow(2, $this->reconnectAttempt) * 30);

        if($connectionDelay > (3600 * 2)) {
            $connectionDelay = (3600 * 2);
        }

        $this->nextActionTime = time() + $connectionDelay;

        $this->reconnectAttempt++;
        $this->logger->info("IRC Client Disconnected.  Trying again in $connectionDelay seconds. (Attempt " . $this->reconnectAttempt . ")");

    }

    public function on_send($data) {

    }

    public function iterate() {
        $this->timers->checkTimers();
        if($this->nextActionTime <= time()) {
            if($this->nextAction == 'connect') {
                echo "next action...\n";
                $this->tryNext();
            } elseif($this->nextAction !== null) {
                $this->logger->warning("nextAction not handled");
            }
            $this->nextAction = null;
        }

        if($this->state == 'attempt-resolve' && ($this->resolveStarted + 60) > time()) {

        }

        //$this->logger->debug("IRC\\Client State: " . $this->state);
        $this->net->iterate();
    }

    protected $maxRecvSize = 0;
    public function on_recv($data) {
        $this->delimitedBufferQueue->receive($data);
        foreach($this->delimitedBufferQueue->consumeAll() as $zk => $msg) {
            $this->logger->debug("RAW < $msg");
            file_put_contents("log/raw-protocol.log", " < $msg\n", FILE_APPEND);
            $this->on_recv_irc($msg);
        }
    }

    public function on_recv_irc($rawData) {
        /** @var \stdClass $data */
        $data = $this->parseIrcCommand($rawData);

        ob_start();
        var_dump($data);
        $contents = ob_get_contents();
        ob_end_clean();
        file_put_contents('log/parsed.log', $contents . "\n\n", FILE_APPEND);


        if($data->cmd == 'ping') {
            $this->sendPong($data->args[0]);
        } else {
            if(method_exists($this, 'on_' . $data->cmd)) {
                // Ideally this wouldn't need the additional logic
                $callbackResult = $this->{'on_' . $data->cmd}($data);
                if($callbackResult !== null) {
                    $data = $callbackResult;
                }
            }

            // Disabled code for sub-modules or per-protocol modules
            foreach($this->modules as $m) {
                $method_name = 'on_' . $data->cmd;
                $this->logger->debug("Trying method $method_name for class '" . get_class($m) . "'");
                if(method_exists($m, $method_name)) {
                    $m->$method_name($this, $data);
                } elseif(isset($cmd['cmd_numeric'])) {
                    $method_name = 'on_numeric_' . $cmd['cmd_numeric'];
                    $this->logger->debug("Trying method $method_name for class '" . get_class($m) . "'");
                    if(method_exists($m, $method_name)) {
                        $m->$method_name($this, $data);
                    }
                }
            }

            // Send this data across the bus for other modules to handle
            $this->bus->publish([
                'protocol' => $this->protocol,
                //'network'  => $this->network,
                //'event'    => 'msg',
                //'from'     => $data->sender,
                //'data'     => $data->data,
                'raw'      => $data->raw,
            ]);
        }
    }

    protected function bPublish($extra) {
        $extra['protocol'] = $this->protocol;
        $extra['network'] = $this->componentName;
        $this->bus->publish($extra);
    }

    protected function on_privmsg(stdClass $line) {
        // TODO: just for testing purposes -- don't forget to remove this!

        //if(substr($line->privmsg->msg, 0, 5) ==  'quit:') {
        //    $this->sendQuit(trim(substr($line->privmsg->msg, 6)));
        //}
        return $line;
    }

    protected function on_err_nicknameinuse(stdClass $cmd) {
        $nick = $this->cfg['nickname'] . '-' . mt_rand(1111, 9999);
        $this->nick = $nick;
        $this->sendNick($nick);
    }

    /**
     * RPL_WELCOME is when we are fully connected
     *
     * @param stdClass $cmd
     */
    protected function on_rpl_welcome(stdClass $cmd) {
        $this->setState('online');

        //$this->sendJoin("#hubbub-test");
        //$this->sendJoin("#hubbub");
    }

    protected function on_rpl_hosthidden(stdClass $line) {

    }

    protected function on_rpl_motd(stdClass $line) {
        $this->serverMotd .= $line->motdLine;
    }

    protected function on_join(stdClass $cmd) {
        if($this->nick == $cmd->hostmask->nick) {
            $this->logger->debug("You have joined some channel(s)");
            foreach($cmd->join['channels'] as $channel) {
                $this->logger->debug("Adding $channel to joined chanenl list");
                $this->channels[$channel] = [
                    'joinedSince' => time(),
                    'names'       => [],
                    'topic'       => null,
                ];

                $this->bPublish([
                    'action'  => 'subscribe',
                    'channel' => $channel,
                ]);
            }

        } else {
            $this->logger->debug("your nick: {$this->nick} and cmd->hostmask->nick: {$cmd->hostmask->nick}");
        }
    }

    protected function on_rpl_topic(stdClass $line) {
        $channel = $line->rpl_topic['channel'];
        $topic = $line->rpl_topic['topic'];
        if(isset($this->channels[$channel])) {
            $this->logger->debug("Set $channel topic to $topic");
            $this->channels[$channel]['topic'] = $topic;

            $this->bPublish([
                'action' => 'topic',
                'channel' => $channel,
                'topic' => $topic
            ]);
        } else {
            $this->logger->alert("Got RPL_TOPIC for channel $channel that I haven't JOIN'd yet");
        }

        //$this->sendMsg($channel, "Topic is: $topic");
        //$this->sendMsg($channel, 'Channel list: ' . Utility::varDump($this->channels));
        //$this->sendMsg($channel, "Server Info: " . Utility::varDump($this->serverInfo));
    }

    protected function on_rpl_namreply(stdClass $line) {
        $channel = $line->rpl_namreply['channel'];
        if(isset($this->channels[$channel])) {
            foreach($line->rpl_namreply['names'] as $name) {
                $this->logger->debug("Added '$name' to $channel name list");
                $this->channels[$channel]['names'][] = $name;
            }
        } else {
            $this->logger->alert("Got RPL_NAMREPLY for channel $channel that I haven't JOIN'd yet");
        }

        $this->bPublish([
            'action' => 'nameList',
            'channel' => $channel,
            'nameList' => $this->channels[$channel]['names']
        ]);

    }

    /*
     * Informational Replies
     */

    protected function on_rpl_yourhost(stdClass $cmd) {
        $this->serverInfo = array_merge($this->serverInfo, [$cmd->cmd => $cmd->{$cmd->cmd}]);
    }

    protected function on_rpl_created(stdClass $cmd) {
        $this->serverInfo = array_merge($this->serverInfo, [$cmd->cmd => $cmd->{$cmd->cmd}]);
    }

    protected function on_rpl_myinfo(stdClass $cmd) {
        $this->serverInfo = array_merge($this->serverInfo, [$cmd->cmd => $cmd->{$cmd->cmd}]);
    }

    protected function on_rpl_isupport(stdClass $cmd) {
        $this->serverInfo = array_merge($this->serverInfo, [$cmd->cmd => $cmd->{$cmd->cmd}]);
    }

    protected function on_rpl_luserclient(stdClass $cmd) {
        $this->serverInfo = array_merge($this->serverInfo, [$cmd->cmd => $cmd->{$cmd->cmd}]);
    }

    protected function on_rpl_luserop(stdClass $cmd) {
        $this->serverInfo = array_merge($this->serverInfo, [$cmd->cmd => $cmd->{$cmd->cmd}]);
    }

    protected function on_rpl_luserunknown(stdClass $cmd) {
        $this->serverInfo = array_merge($this->serverInfo, [$cmd->cmd => $cmd->{$cmd->cmd}]);
    }

    protected function on_rpl_luserchannels(stdClass $cmd) {
        $this->serverInfo = array_merge($this->serverInfo, [$cmd->cmd => $cmd->{$cmd->cmd}]);
    }

    protected function on_rpl_luserme(stdClass $cmd) {
        $this->serverInfo = array_merge($this->serverInfo, [$cmd->cmd => $cmd->{$cmd->cmd}]);
    }

    protected function on_rpl_localusers(stdClass $cmd) {
        $this->serverInfo = array_merge($this->serverInfo, [$cmd->cmd => $cmd->{$cmd->cmd}]);
    }

    protected function on_rpl_globalusers(stdClass $cmd) {
        $this->serverInfo = array_merge($this->serverInfo, [$cmd->cmd => $cmd->{$cmd->cmd}]);
    }
}