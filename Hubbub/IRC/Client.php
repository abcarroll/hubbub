<?php

/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2013, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */


namespace Hubbub\IRC;

use StdClass;

/**
 * Thoughts: Instead of extending \Hubbub\Net\Stream\Client perhaps it should use dependency injection instead so you could
 * actually use the other kinds of Networking if necessary?
 */

/**
 * Class Client
 *
 * @package Hubbub\Modules\IRC
 */
class Client extends \Hubbub\Net\Client {
    use Parser;
    use Senders;

    /** @var  \Hubbub\Net\Generic\Client */
    protected $protocol = 'irc';
    protected $state;

    protected $recvBuffer = ''; // Where incomplete, fragmented commands go for temporary storage

    public $serverList = [];
    public $serverInfo = [];
    public $cfg = [
        'nickname' => 'Hubbub-Z',
        'username' => 'Hubbub',
        'realname' => 'Bob Marley',
    ];

    public function __construct(\Hubbub\Hubbub $hubbub = null, $confName) {
        parent::__construct($hubbub);
        $this->state = 'pre-auth';

        $this->serverList = $this->hubbub->conf->get($confName . '.serverList');

        $this->net->connect("tcp://{$this->serverList[0]}");
    }

    /* --- Properly set states on connect and disconnect --- */
    public function on_connect($socket = null) {
        $this->sendUser($this->cfg['username'], $this->cfg['realname']);
        $this->sendNick($this->cfg['nickname']);
        $this->state = 'gave-auth';
    }

    public function send($data) {
        var_dump($data);
        return $this->net->send("$data\n");
    }

    public function on_disconnect() {
        echo "\\Hubbub\\IRC\\Client::on_disconnect() called\n";
    }

    public function on_send($data) {

    }

    public function iterate() {
        $this->net->iterate();
    }

    public function on_recv($data) {

        $this->recvBuffer .= $data;

        $commands = explode("\r\n", $this->recvBuffer);
        $lastIdx = count($commands) - 1;

        $incomplete = '';
        if($commands[$lastIdx] !== "") {
            $incomplete .= $commands[$lastIdx - 1];
            $this->hubbub->logger->debug("Last command was a fragement; storing in buffer: {$commands[$lastIdx - 1]}");
        }

        $this->recvBuffer = $incomplete;

        foreach($commands as $line) {
            $this->on_recv_irc($line);
        }
    }

    public function on_recv_irc($rawData) {
        echo " => $rawData\n";

        /** @var \StdClass $data */
        $data = $this->parseIrcCommand($rawData);

        print_r($data);

        if($data->cmd == 'ping') {
            $this->sendPong($data['parm']);
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
            /*$this->hubbub->bus->publish([
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


        var_dump($cmd);

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