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
use StdClass;

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
    protected $net;
    protected $logger, $bus, $conf;

    /** @var  \Hubbub\Net\Generic\Client */
    protected $protocol = 'irc';
    protected $state = 'initialize';

    protected $recvBuffer = ''; // Where incomplete, fragmented commands go for temporary storage

    public $serverList = [];
    public $serverInfo = [];
    public $cfg = [
        'nickname' => 'Hubbub-Z',
        'username' => 'Hubbub',
        'realname' => 'Bob Marley',
    ];

    protected $currentServerIdx = 0;
    protected $nextAction = 'connect';
    protected $nextActionTime = 0;

    public function __construct(\Hubbub\Net\Client $net, \Hubbub\Logger $logger, \Hubbub\MessageBus $bus, \Hubbub\Configuration $conf, $name) {
        $this->net = $net;
        $this->logger = $logger;
        $this->bus = $bus;
        $this->conf = $conf;

        // Set the network's protocol to ths IRC object; the IRC object will receive event notifications via the network handler
        $this->net->setProtocol($this);

        $this->serverList = $this->conf->get($name . '.serverList');
        $this->connectNext();
    }

    protected function connectNext() {
        $this->state = 'pre-auth';
        $nextServer = $this->serverList[($this->currentServerIdx++ % count($this->serverList))];
        $this->logger->info("Connecting to: $nextServer");
        $this->net->connect("tcp://$nextServer");
    }

    /* --- Properly set states on connect and disconnect --- */
    public function on_connect($socket = null) {
        $this->sendUser($this->cfg['username'], $this->cfg['realname']);
        $this->sendNick($this->cfg['nickname']);
        $this->state = 'gave-auth';
    }

    public function send($data) {
        return $this->net->send("$data\n");
    }


    public function on_disconnect() {
        $this->state = 'disconnected';
        $this->nextAction = 'connect';
        $this->nextActionTime = time() + 30;
        $this->logger->info("IRC Client Disconnected.  Trying again in 30 seconds.");
    }

    public function on_send($data) {

    }

    public function iterate() {
        if($this->nextActionTime <= time()) {
            if($this->nextAction == 'connect') {
                $this->connectNext();
            } elseif($this->nextAction !== null) {
                $this->logger->warning("nextAction not handled");
            }
            $this->nextAction = null;
        }

        //$this->logger->debug("IRC\\Client State: " . $this->state);
        $this->net->iterate();
    }

    public function on_recv($data) {

        $this->recvBuffer .= $data;

        $commands = explode("\r\n", $this->recvBuffer);
        $lastIdx = count($commands) - 1;

        $incomplete = '';
        if($commands[$lastIdx] !== "") {
            $incomplete .= $commands[$lastIdx - 1];
            $this->logger->debug("Last command was a fragment; storing in buffer: {$commands[$lastIdx - 1]}");
        }

        $this->recvBuffer = $incomplete;

        foreach($commands as $line) {
            $this->on_recv_irc($line);
        }
    }

    public function on_recv_irc($rawData) {
        $this->logger->debug("RAW: $rawData");

        /** @var \StdClass $data */
        $data = $this->parseIrcCommand($rawData);
        if($data->cmd == 'ping') {
            $this->sendPong($data->parm);
        } else {
            if(method_exists($this, 'on_' . $data->cmd)) {
                $data = $this->{'on_' . $data->cmd}($data);
            }

            /* foreach($this->modules as $m) {
                $method_name = 'on_' . $d['cmd'];
                cout(odebug, "Trying method $method_name for class '" . get_class($m) . "'");
                if(method_exists($m, $method_name)) {
                    $m->$method_name($this, $d);
                } elseif(isset($cmd['cmd_numeric'])) {
                    $method_name = 'on_numeric_' . $cmd['cmd_numeric'];
                    cout(odebug, "Trying method $method_name for class '" . get_class($m) . "'");
                    if(method_exists($m, $method_name)) {
                        $m->$method_name($this, $d);
                    }
                }
            } */ # end foreach($commands


            // Finally should do something like this
            /*$this->bus->publish([
              'protocol' => $this->protocol,
              //'network'  => $this->network,
              'event'    => 'msg',
              'from'     => $data['sender'],
              'data'     => $data['data'],
              'irc'      => $data,
          ]);*/
        }
    }

    protected function on_rpl_welcome(StdClass $cmd) {
        $this->sendJoin("#hubbub");
    }

    protected function on_join(StdClass $cmd) {
        // todo Implement on_join() method
    }

    /*
     * Informational Replies
     */

    protected function on_rpl_yourhost(StdClass $cmd) {
        $this->serverInfo = array_merge($this->serverInfo, [$cmd->cmd => $cmd->{$cmd->cmd}]);
    }

    protected function on_rpl_created(StdClass $cmd) {
        $this->serverInfo = array_merge($this->serverInfo, [$cmd->cmd => $cmd->{$cmd->cmd}]);
    }

    protected function on_rpl_myinfo(Stdclass $cmd) {
        $this->serverInfo = array_merge($this->serverInfo, [$cmd->cmd => $cmd->{$cmd->cmd}]);
    }

    protected function on_rpl_isupport(Stdclass $cmd) {
        $this->serverInfo = array_merge($this->serverInfo, [$cmd->cmd => $cmd->{$cmd->cmd}]);
    }

    protected function on_rpl_luserclient(Stdclass $cmd) {
        $this->serverInfo = array_merge($this->serverInfo, [$cmd->cmd => $cmd->{$cmd->cmd}]);
    }

    protected function on_rpl_luserop(Stdclass $cmd) {
        $this->serverInfo = array_merge($this->serverInfo, [$cmd->cmd => $cmd->{$cmd->cmd}]);
    }

    protected function on_rpl_luserunknown(Stdclass $cmd) {
        $this->serverInfo = array_merge($this->serverInfo, [$cmd->cmd => $cmd->{$cmd->cmd}]);
    }

    protected function on_rpl_luserchannels(Stdclass $cmd) {
        $this->serverInfo = array_merge($this->serverInfo, [$cmd->cmd => $cmd->{$cmd->cmd}]);
    }

    protected function on_rpl_luserme(Stdclass $cmd) {
        $this->serverInfo = array_merge($this->serverInfo, [$cmd->cmd => $cmd->{$cmd->cmd}]);
    }

    protected function on_rpl_localusers(Stdclass $cmd) {
        $this->serverInfo = array_merge($this->serverInfo, [$cmd->cmd => $cmd->{$cmd->cmd}]);
    }

    protected function on_rpl_globalusers(Stdclass $cmd) {
        $this->serverInfo = array_merge($this->serverInfo, [$cmd->cmd => $cmd->{$cmd->cmd}]);
    }
}