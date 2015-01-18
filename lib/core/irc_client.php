<?php

/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2013, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */

class irc_client extends net_stream_client {
    use generic_irc;

    private $state;

    public $cfg = [
        'nickname' => 'HubTest',
        'username' => 'HubTest',
        'realname' => 'Hubbub',
        'server'   => 'tcp://irc.freenode.net:6667',
    ];

    public function __construct($hubbub) {
        net_stream_client::__construct($hubbub);
        $this->state = 'pre-auth';
        $this->connect($this->cfg['server']);
        $this->set_blocking(false);
    }

    /* --- Properly set states on connect and disconnect --- */
    function on_connect($socket = null) {
        $this->state = 'gave-auth';
        $this->nick($this->cfg['nickname']);
        $this->user($this->cfg['username'], $this->cfg['realname']);
    }


    function recv($length = 4096) {

    }

    function send($data) {
        return parent::send("$data\n");
    }

    function on_disconnect() {
        echo "Disconnected\n";
        die;
    }

    function on_send($data) {

    }

    function iterate() {
        echo "ITER IRC CLIENT\n";
        parent::iterate();
    }

    function on_recv_irc($raw_data, $socket = null) {
        $d = $this->parse_irc_cmd($raw_data);

        print_r($d);

        if($d['cmd'] == 'ping') {
            $this->pong($d['parm']);
        } else {
            // 1 - For numeric commands, the command has already been translated to the RFC compatible name
            //	.. like 001 is now rpl_welcome
            // 2 - Check for the existance of on_* for that command, like on_rpl_welcome() or on_privmsg()
            // 3 - If not found, check for the raw numeric code, like on_numeric_001()

            // Instead, call self-modules and submodules.. notifier should be implemented as a submodule

            // on rpl_welcome it should set state = online

            // where did my timers go?

            // this is getting confusing..

            if(method_exists($this, 'on_' . $d['cmd'])) {
                $this->{'on_' . $d['cmd']}($d);
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
        }
    }

    function on_rpl_welcome($d) {
        $this->join("#hubbub");
    }


    function on_privmsg($d) {
        if($d['privmsg']['msg'] == 'Hello') {
            $this->privmsg($d['privmsg']['to'], "Hi");
        }
    }


    // function ...

}
