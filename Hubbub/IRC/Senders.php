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

/**
 * Class Generic
 *
 * @package Hubbub\Modules\IRC
 */
trait Senders {
    /**
     * @param $data
     */
    public function on_recv($data) {
        $commands = explode("\n", $data);
        if(!empty($commands[count($commands) - 1])) {
            $incomplete = $commands[count($commands) - 1];
            $commands[count($commands) - 1] = '';
            // TODO Use proper logger
            trigger_error("Received incomplete command '$incomplete' - discarding", E_USER_WARNING);
        }

        foreach($commands as $c) {
            if(!empty($c)) {
                $this->on_recv_irc($c);
            }
        }
    }

    /*
     * IRC Protocol Implementation for Client-to-Server Communication
     * Ping & Ping are defined in 4.6[.2 & .3] of rfc1459.
     * The chapter information accompanying each section below is in reference to the old 1495 spec.
     */

    /**
     * Sends a PING to the server, with the expectation to receive a PONG.
     *
     * @param string $server
     */
    public function sendPing($server) {
        if(is_array($server)) {
            $this->send("PING " . implode(' ', $server));
        } else {
            $this->send("PING $server");
        }
    }

    /**
     * Sends a PONG to the server, generally after a PING has been receieved.
     *
     * @param string $server
     */
    public function sendPong($server) {
        if(is_array($server)) {
            $this->send("PONG " . implode(' ', $server));
        } else {
            $this->send("PONG $server");
        }
    }

    /*
     * 4.1 Message Details
     */

    /**
     * Sends the PASS command to the server to authenticate the client.
     *
     * @param string $pass The password to send
     */
    public function sendPass($pass) {
        if($this->state != 'unregistered') {
            cout(owarning, "Sending PASS in a non-unregistered state ({$this->state})");
        }
        $this->send('PASS ' . $pass);
    }

    /**
     * Sends the NICK command to the server, to change the nickname.
     *
     * @param string $nick
     */
    public function sendNick($nick) {
        $this->send('NICK ' . $nick);
    }

    /**
     * Sends the USER command to the server.
     *
     * @param string $username
     * @param string $realname
     *
     * @todo I don't think this is up to date with the modern specification
     */
    public function sendUser($username, $realname) { // note we drop <hostname> and <servername> since we'll be "locally connected"
        $this->send('USER ' . $username . ' 0 0 :' . $realname);
    }

    /**
     * Sends the OPER command to the server.
     *
     * @param string $username
     * @param string $password
     */
    public function sendOper($username, $password) {
        $this->send('OPER ' . $username . ' ' . $password);
    }

    /**
     * @param string $msg The quit message
     */
    public function sendQuit($msg = "Gone to have lunch") {
        $this->send('QUIT :' . $msg);
    }

    /*
     * 4.2 Channel operations
     */

    /**
     * Joins a channel.
     *
     * @param string $channel
     * @param string $key
     *
     * @todo Should be able to pass an array here and also be able to manage a buffer / use RPL_ISUPPORT to maximize effeciency
     */
    public function sendJoin($channel, $key = '') {
        // TODO check if we're already in that channel
        // TODO maintain channel list that we're actively involvedi n
        if(!empty($key)) {
            $this->send("JOIN $channel $key");
        } else {
            $this->send("JOIN $channel");
        }
    }

    /**
     * Parts a channel.
     *
     * @param string $channel The channel to part
     */
    public function sendPart($channel) {
        // TODO maintain channel list that we're actively involved in
        $this->send("PART $channel");
    }

    /**
     * @todo Implement this function, send MODE command for channels and users.
     */
    public function sendMode() {
        trigger_error("::sendMode() not implemented.", E_USER_WARNING);
    }

    /**
     * @param string $channel The channel to set the topic for.
     * @param string $topic   The topic to set for the channel.
     */
    public function setTopic($channel, $topic) {
        $this->send("TOPIC $channel :$topic");
    }

    /**
     * @param string $channel The channel to get the topic for.
     */
    public function getTopic($channel) {
        $this->send("TOPIC $channel");
    }

    /**
     * @todo NAMES command
     * @todo LIST  command
     */

    /**
     * Invites a user to the channel specified.  The paraters are reversed from the protocol implementation for consistency of our own API.
     *
     * @param string $channel The channel to invite the user to.
     * @param string $user    The user to invite.
     */
    public function sendInvite($channel, $user) {
        $this->send("INVITE $user $channel");
    }

    /**
     * @param string $channel The channel to kick said user out of.
     * @param string $user    The user to kick.
     * @param string $comment An optional parting comment for the user.
     */
    public function sendKick($channel, $user, $comment = "") {
        if(empty($comment)) {
            $this->send("KICK $channel $user");
        } else {
            $this->send("KICK $channel $user :$comment");
        }
    }

    /**
     * @param bool $server
     */
    public function sendVersion($server = false) {
        if(!$server) {
            $this->send("VERSION");
        } else {
            $this->send("VERSION $server");
        }
    }

    /**
     * @param bool $server
     */
    public function askServerTime($server = false) {
        if(!$server) {
            $this->send("TIME");
        } else {
            $this->send("TIME $server");
        }
    }

    // Can STATS support more than one query type at once?
    // If so, maybe transform this into a higher level function ...
    public function askServerStats($query, $server = false) {
        if(!$server) {
            $this->send("STATS $query");
        } else {
            $this->send("STATS $query $server");
        }
    }

    /**
     * @param bool $server
     */
    public function sendServerAdmin($server = false) {
        if(!$server) {
            $this->send("ADMIN");
        } else {
            $this->send("ADMIN $server");
        }
    }

    /**
     * @param bool $server
     */
    public function sendServerInfo($server = false) {
        if(!$server) {
            $this->send("INFO");
        } else {
            $this->send("INFO $server");
        }
    }

    /*
     * 4.4 PRIVMSG and NOTICE
     */

    /**
     * @param $who
     * @param $what
     */
    public function sendMsg($who, $what) {
        $this->send("PRIVMSG $who :$what");
    }

    /**
     * Sends a CTCP message to the specified user or chnanel.
     *
     * @param string $who  The person or channel to send the CTCP message to.
     * @param string $what The CTCP message to send, as "COMMAND ARGUMENT" or simply "COMMAND" if no argument.
     */
    public function sendCtcp($who, $what) {
        $this->notice($who, chr(1) . $what . chr(1));
    }

    /**
     * Sends a NOTICE to the specified user or chnanel.
     *
     * @param string $who  The person or channel to send the NOTICE message to.
     * @param string $what The NOTICE message text.
     */
    public function sendNotice($who, $what) {
        $this->send("NOTICE $who :$what");
    }

    /*
     * 4.5 User Based Queries
     */

    /**
     * @todo WHO Command
     * @todo WHOIS command
     * @todo WHOWAS command
     * @todo KILL command
     */

    /*
     * 5.0 Options
     */

    /**
     * @param string $msg The away message to use.  Defaults to 'Away'
     */
    public function sendAway($msg = '') {
        if(empty($msg)) {
            $msg = 'Away';
        }
        // $this->away = true;
        $this->send('AWAY ' . $msg);
    }

    /**
     * Marks the user as no longer away.
     */
    public function sendUnaway() {
        // $this->away = false;
        $this->send('AWAY');
    }

    /**
     * @todo REHASH
     * @todo RESTART
     * @todo SUMMON
     * @todo WALLOPS
     * @todo USERHOST
     * @todo ISON
     * @todo USERS
     */
}