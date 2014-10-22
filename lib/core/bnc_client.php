<?php

    /*
     * This file is a part of Hubbub, freely available at http://hubbub.sf.net
     *
     * Copyright (c) 2013, Armond B. Carroll <ben@hl9.net>
     * For full license terms, please view the LICENSE.txt file that was
     * distributed with this source code.
     */

    class bnc_client { // extends ??, this is a bit new..
        use generic_irc;

        private $hubbub, $bnc, $socket;
        private $connect_time, $state = 'pre-reg';

        private $incoming_nick, $incoming_user;

        function __construct($hubbub, $bnc, $socket) {
            $this->hubbub = $hubbub;
            $this->bnc = $bnc;
            $this->socket = $socket;
        }

        function disconnect() {
            fclose($this->socket); // sorta works
        }

        function send($command) {
            $this->bnc->send($this->socket, "$command\n");
        }

        function on_recv_irc($c) {
            $c = $this->parse_irc_cmd($c);

            $try_method = 'on_' . $c['cmd'];
            if(method_exists($this, $try_method)) {
                $this->$try_method($c);
            } else {
                $this->on_unhandled($c);
            }
        }

        function on_unhandled($c) {
            $this->hubbub->logger->notice("Unhandled IRC Command: {$c['cmd']}");
        }

        function on_nick($c) {
            $this->incoming_nick = $c['parm'];
            $this->try_registration();
        }

        function on_user($c) {
            $this->incoming_user = $c['parm'];
            $this->try_registration();
        }

        function try_registration() {
            if(!empty($this->incoming_nick) && !empty($this->incoming_user)) {
                $this->state = 'pre-auth';
                $this->notice("*", "I'm going to have to ask you see your I.D.");
                $this->notice("*", "Type /QUOTE PASS <yourpass> now.");
            }
        }

        function on_pass($c) {
            if($this->state != 'pre-auth') {
                $this->notice("*", "PASS Sequence out of order");
            } else {
                $compare = trim(file_get_contents('var/passwd'));
                if($c['parm'] == $compare) {
                    $this->welcome();
                } else {
                    $this->hubbub->logger->notice("Failed login, {$c['parm']} != $compare");
                    $this->disconnect(); // Not implemented, WILL cause fatal error.
                }
            }
        }

        function welcome() {
            $this->send(":Hubbub 001 {$this->incoming_nick} WELCOME");
            $f = file("LICENSE.txt");
            $this->send(":Hubbub 001 {$this->incoming_nick} :Welcome to Hubbub's Internet Relay Chat Proxy, " . $this->incoming_nick);
            $this->send(":Hubbub 375 {$this->incoming_nick} :MOTD AS FOLLOWS");
            foreach ($f as $line) {
                $this->send(":Hubbub 372 {$this->incoming_nick} : - " . trim($line));
            }
            $this->send(":Hubbub 375 {$this->incoming_nick} :END OF MOTD");
            $this->send(":-Hubbub!Hubbub@Hubbub. PRIVMSG {$this->incoming_nick} :Welcome back, cowboy!");
        }

        function iterate() {
            $this->hubbub->logger->debug("BNC Client #" . ((int) $this->socket) . " was iterated.");
        }
    }
