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

namespace Hubbub\Net\Socket;

/**
 * Class Generic
 * @package Hubbub\Net\Socket
 */
abstract class Generic {
    /**
     * @var resource As a client, this will be the client socket.  As a server, this will be the listening socket.
     */
    protected $socket;

    /**
     * @var bool Is the socket blocking (true) or non-blocking (false)?
     */
    protected $isBlocking;

    /**
     * @var string The simplified status of the socket represented as a string.
     */
    protected $socketState = 'stateless'; // stateless, listening, connecting, connected, disconnected

    /**
     * Create a low level socket.  This method is able to socket_create() any type of client or listening socket using simple string inputs.
     *
     * Other considerations: (1) From what I understand SOCK_SEQPACKET is not well supported even across major OS's so it is disregarded here.  If anyone would
     * like to write/test a reliable way to both support it via the create() function's parameters and all other considerations, they are more than welcome. I
     * believe AF_TIPC may be useful.
     *
     * @param string    $type     The type of socket, as a string.  Valid values: inet, inet4, inet6, unix, file
     * @param string    $protocol The protocol of the socket, as a string.  Valid values: tcp, udp, icmp, rdm, and [unsupported] tcpseq.
     * @param bool|true $blocking Should the socket be blocking, if it makes sense in this context?  Defaults to true.
     */
    public function create($type, $protocol, $blocking = true) {
        $type_table = array(
            'inet'  => AF_INET, // TODO auto-select ipv4/ipv6 based on later events
            'inet4' => AF_INET,
            'inet6' => AF_INET6,
            'unix'  => AF_UNIX,
            'file'  => AF_UNIX,
        );

        $proto_table = array(
            'tcp'  => SOCK_STREAM,
            'udp'  => SOCK_DGRAM,
            #'tcpseq' => SOCK_SEQPACKET,  // Something like this or a 3rd parameter to create() would be the way to implement it
            'icmp' => SOCK_RAW,
            'rdm'  => SOCK_RDM, // for completeness
        );

        $protonumber = getprotobyname($protocol);

        if(($this->socket = socket_create($type_table[$type], $proto_table[$protocol], $protonumber)) < 0) {
            $errorMsg = $this->lastErrorMsg();
            trigger_error("Couldn't create socket: $errorMsg", E_USER_WARNING);
            return;
        } else {
            if($blocking) {
                $this->setBlocking();
            } else {
                $this->setBlocking(false);
            }
        }
    }

    public function bind($src_ip) {
        // TODO can't call bind() when already connected
        if($this->socketState == 'connected' && ($socket_bind = @socket_bind($this->socket, $src_ip)) < 0) {
            trigger_error("socket_bind() has failed: either already connected, or the call failed.");
            return false;
        } else {
            return true;
        }
    }

    // send() and recv() probably only work for tcp
    public function send($data, $socket = null) {
        if($socket === null) {
            $socket = $this->socket;
        }
        $r = socket_write($socket, $data);
        $this->on_send($data, $socket);

        return $r;
    }

    public function recv($socket = null) {
        // on_recv() is handled in the socket_poll() to handle disconnections
        // for server classes.  if it could be moved here in a generic way,
        // then that would what would be done.  TODO
        if($socket === null) {
            $socket = $this->socket;
        }

        $data = socket_read($socket, 4096);
        #$bytes = socket_recv($socket, $data, 4096, 0x40);
        if($data !== false) {
            $this->on_recv($data, $socket);
        }

        return $data;
    }

    // blocking/nonblocking
    public function setBlocking($blocking = true) {
        $this->isBlocking = $blocking;
        if($blocking) {
            return socket_set_block($this->socket);
        } else {
            return socket_set_nonblock($this->socket);
        }
    }

    public function isBlocking() {
        return $this->isBlocking;
    }

    public function lastErrorMsg($socket = null) {
        if($socket === null) {
            $socket = $this->socket;
        }
        $errorNumber = socket_last_error($socket);
        $errorMsg = socket_strerror($errorNumber);
        socket_clear_error($socket);
        return "[$errorNumber] $errorMsg";
    }

    abstract public function
}