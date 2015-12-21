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

/**
 * Class BncClient
 * @package Hubbub\IRC
 */
class BncClient { // TODO make a base class!
    use Parser;
    use Senders;

    protected $bnc, $logger, $conf, $bus, $socket;
    /**
     * @var string $state         The state of the connection.  Possible values are 'preauth', 'unregistered', 'registered'.
     * @var int    $auth_attempts The number of authenticiation attempts.  Currently hard-coded to a maximum of 3 attempts.
     * @
     */
    protected $state = 'preauth', $auth_attempts = 0, $max_auth_attempts = 3, $nick, $user, $connect_time, $state_time;

    function __construct(\Hubbub\IRC\Bnc $bnc, \Hubbub\Logger $logger, \Hubbub\Configuration $conf, \Hubbub\MessageBus $bus, $socket) {
        $this->bnc = $bnc;
        $this->logger = $logger;
        $this->conf = $conf;
        $this->bus = $bus;
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

    function onBusMessage($b) {
        if($this->state == 'registered') {
            $this->logger->debug("I received a bus message: ");
            $this->logger->debug(print_r($b, true));
            $this->send(":-Hubbub!Hubbub@Hubbub. PRIVMSG {$this->nick} :" . $b['raw']);
        }
    }

    function on_recv_irc($c) {
        $c = $this->parseIrcCommand($c);

        $try_method = 'on_' . strtolower($c->cmd);
        if(method_exists($this, $try_method)) {
            $this->$try_method($c);
        } else {
            $this->on_unhandled($c);
        }
    }

    function on_unhandled($c) {
        $this->logger->debug("Unhandled IRC Command: " . $c->cmd);
    }

    function on_nick($c) {
        $this->logger->debug("Got NICK: " . $c->argData);
        $this->nick = $c->argData;
        $this->tryRegistration();
    }

    function on_user($c) {
        $this->logger->debug("Got USER: " . $c->argData);
        $this->user = $c->argData;
        $this->tryRegistration();
    }

    function tryRegistration() {
        if(!empty($this->nick) && !empty($this->user)) {
            $this->state = 'unregistered';
            $this->state_time = time();
            $this->sendNotice("*", "I'm going to have to ask to see your ID, " . $this->nick);
            $this->sendNotice("*", "Type /QUOTE PASS <yourpass> now.");
            $this->logger->debug("Moved from preauth to unregistered");
        } else {
            $this->logger->debug("Registration failed: Not enough data");
        }
    }

    function on_pass($c) {
        if($this->state != 'unregistered') {
            $this->sendNotice("*", "PASS Sequence out of order.  You must first NICK and USER.");
            $this->disconnect();
        } else {
            $compare = $this->conf->get('irc.bnc.password');
            if($c->argData == $compare) {
                $this->welcome();
            } else {
                $this->logger->notice("Failed login, " . $c->argData . " != $compare");
                $this->sendNotice(':', "Failed login, try again?");
                $this->auth_attempts++;

                if($this->auth_attempts > $this->max_auth_attempts) {
                    $this->logger->notice("Too many failed login attempts.");
                    $this->sendNotice(':', "Too many failed login attempts.");
                    $this->disconnect();
                }

            }
        }
    }

    function welcome() {
        $this->state = 'registered';

        $this->send(":Hubbub 001 {$this->nick} WELCOME");
        $motdFile = $this->conf->get('irc.bnc.motd_file');
        if(is_readable($motdFile)) {
            $f = file($this->conf->get('irc.bnc.motd_file'));
        } else {
            $f = ["The config value irc.bnc.motd_file was unreadable"];
        }

        $this->send(":Hubbub 001 {$this->nick} : Welcome to Hubbub's Internet Relay Chat Proxy, " . $this->nick);
        $this->send(":Hubbub 375 {$this->nick} : MOTD AS FOLLOWS");
        foreach($f as $line) {
            $this->send(":Hubbub 372 {$this->nick} : - " . trim($line));
        }
        $this->send(":Hubbub 375 {$this->nick} :END OF MOTD");

        $this->send(":-Hubbub!Hubbub@Hubbub. PRIVMSG {$this->nick} :Welcome back, cowboy!");

        //$this->sendJoin("#hubbub");

    }

    function iterate() {
        // $this->logger->debug("BNC Client #" . ((int) $this->socket) . " was iterated.");
        // Check state times for expiration
        /*if(($this->state == 'preauth' || $this->state == 'unregistered') && (time() - $this->state_time) > 30) {
            $this->sendNotice('*', "Client timeout.  Try again later.");
            //$this->disconnect(); // TODO !!!
        }*/
    }

    function on_privmsg($c) {
        var_dump($c);
        $this->bus->publish([
            'originate' => true,
            'protocol'  => 'irc',
            'from'      => $this->nick,
            'message'   => $c->argData,
        ]);

    }
}