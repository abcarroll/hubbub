<?php

/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2013, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */

namespace Hubbub\IRC;

/**
 * Thoughts: Instead of extending \Hubbub\Net\Stream\Client perhaps it should use dependency injection instead so you could
 * actually use the other kinds of Networking if necessary?
 */

/**
 * Class Client
 *
 * @package Hubbub\Modules\IRC
 */
class Client extends \Hubbub\Net\Stream\Client implements \Hubbub\IterableModule {
    // Numerics is already pulled in via Parser
    // use Numerics;
    use Parser;
    use Senders;

    private $protocol = 'irc';
    private $network = 'freenode';
    private $state;

    public $cfg = [
        'nickname' => 'HubTest',
        'username' => 'php',
        'realname' => 'Hubbub',
        'server'   => 'tcp://irc.freenode.net:6667',
    ];

    public function __construct(\Hubbub\Hubbub $hubbub) {
        $this->cfg['nickname'] = 'HubTest-' . dechex(mt_rand(0, 255));
        parent::__construct($hubbub);
        $this->bus = $hubbub->bus;
        $this->state = 'pre-auth';
        $this->connect($this->cfg['server']);
        $this->set_blocking(false);
    }

    /* --- Properly set states on connect and disconnect --- */
    public function on_connect($socket = null) {
        $this->state = 'gave-auth';
        $this->sendNick($this->cfg['nickname']);
        $this->sendUser($this->cfg['username'], $this->cfg['realname']);
    }


    public function recv($length = 4096) {

        die("Used?");

    }

    public function send($data) {
        return parent::send("$data\n");
    }

    public function on_disconnect() {
        echo "Disconnected\n";
    }

    public function on_send($data) {
        /* TODO
         * does this even fire?
         */
    }

    public function iterate() {
        parent::iterate();
    }

    public function on_recv_irc($rawData, $socket = null) {
        echo " => $rawData\n";

        /** @var \StdClass $data */
        $data = $this->parseIrcCommand($rawData);

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
            /* $this->bus->publish([
              'protocol' => $this->protocol,
              'network'  => $this->network,
              'event'    => 'msg',
              'from'     => $d['sender'],
              'data'     => $d['data'],
              'irc'      => $d,
          ]); */
        }
    }

    public function on_rpl_welcome() {
        $this->sendJoin("#hubbub");
    }

}
