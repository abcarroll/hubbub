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
 * $this->state:
 */

/**
 * Class BncClient
 * This class represents a client connection on the BNC server.
 */
class BncClient { // TODO make a base class!
    use Generic;

    private $hubbub, $bnc, $socket;
    /**
     * @var string $state         The state of the connection.  Possible values are 'preauth', 'unregistered', 'registered'.
     * @var int    $auth_attempts The number of authenticiation attempts.  Currently hard-coded to a maximum of 3 attempts.
     * @
     */
    private $state = 'preauth', $auth_attempts = 0, $max_auth_attempts = 3, $nick, $user, $connect_time, $state_time;

    function __construct(\Hubbub\Hubbub $hubbub, \Hubbub\IRC\Bnc $bnc, $socket) {
        $this->hubbub = $hubbub;
        $this->bnc = $bnc;
        $this->socket = $socket;
        $this->connect_time = time();
        $this->state_time = time();
    }

    function disconnect() {
        stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);
        //$this->bnc->disconnectClient($this->socket);
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
        $this->hubbub->logger->debug("Unhandled IRC Command: {$c['cmd']}");
    }

    function on_nick($c) {
        $this->hubbub->logger->debug("Got NICK: " . $c['parm']);
        $this->nick = $c['parm'];
        $this->tryRegistration();
    }

    function on_user($c) {
        $this->hubbub->logger->debug("Got USER: " . $c['parm']);
        $this->user = $c['parm'];
        $this->tryRegistration();
    }

    function tryRegistration() {
        if(!empty($this->nick) && !empty($this->user)) {
            $this->state = 'unregistered';
            $this->state_time = time();
            $this->notice("*", "I'm going to have to ask to see your ID, " . $this->nick);
            $this->notice("*", "Type /QUOTE PASS <yourpass> now.");
            $this->hubbub->logger->debug("Moved from preauth to unregistered");
        } else {
            $this->hubbub->logger->debug("Registration failed: Not enough data");
        }
    }

    function on_pass($c) {
        if($this->state != 'unregistered') {
            $this->notice("*", "PASS Sequence out of order.  You must first NICK and USER.");
            $this->disconnect();
        } else {
            $compare = '1234';
            if($c['parm'] == $compare) {
                $this->welcome();
            } else {
                $this->hubbub->logger->notice("Failed login, {$c['parm']} != $compare");
                $this->notice(':', "Failed login, try again?");
                $this->auth_attempts++;

                if($this->auth_attempts > $this->max_auth_attempts) {
                    $this->hubbub->logger->notice("Too many failed login attempts.");
                    $this->notice(':', "Too many failed login attempts.");
                    $this->disconnect();
                }

            }
        }
    }

    function welcome() {
        $this->state = 'registered';

        $this->send(":Hubbub 001 {$this->nick} WELCOME");
        $f = file("LICENSE.txt");
        $this->send(":Hubbub 001 {$this->nick} : Welcome to Hubbub's Internet Relay Chat Proxy, " . $this->nick);
        $this->send(":Hubbub 375 {$this->nick} : MOTD AS FOLLOWS");
        foreach ($f as $line) {
            $this->send(":Hubbub 372 {$this->nick} : - " . trim($line));
        }
        $this->send(":Hubbub 375 {$this->nick} :END OF MOTD");

        $this->send(":-Hubbub!Hubbub@Hubbub. PRIVMSG {$this->nick} :Welcome back, cowboy!");

        $this->join("#hubbub");
    }

    function iterate() {
        // $this->hubbub->logger->debug("BNC Client #" . ((int) $this->socket) . " was iterated.");
        // Check state times for expiration
        /*if(($this->state == 'preauth' || $this->state == 'unregistered') && (time() - $this->state_time) > 30) {
            $this->notice('*', "Client timeout.  Try again later.");
            //$this->disconnect(); // TODO !!!
        }*/
    }

    function on_privmsg($c) {
        $this->hubbub->bus->publish([
            'originate' => true,
            'protocol' => 'irc',
            'sender'   => 'Me',
        ]);

    }
}
