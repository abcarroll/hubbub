<?php
/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2013, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */

namespace Hubbub\Net\Socket;

    /**
     * This is a generic implementation using PHP's wrapper around Berkley sockets.  It is marked as not being used by Hubbub and was written separately.
     *
     * @todo Fix & use this properly, or remove it.
     */

/**
 * Class Generic
 *
 * @package Hubbub\Net\Socket
 */
abstract class Generic {
    protected $is_blocking;
    // As a client, this will be the client socket;
    // As a server, it is meant for the master/binded socket
    protected $socket;
    protected $extra_sockets = array();

    protected $low_socket_state = 'stateless'; // stateless, listening, connecting, connected, disconnected

    // valid options
    // [inet[[4]|6]|file],{udp|tcp|tcpseq}

    // Other considerations:
    // - From what I understand SOCK_SEQPACKET is not well supported even across major OS's so it is disregarded
    //	here.  If anyone would like to write/test a reliable way to both support it via the create() function's
    //	parameters and all other considerations, they are more than welcome.
    // - I believe AF_TIPC may be useful later on (if not yesterday) - once again, same as above
    // - And so on, and so on with the plethora of PF/AF/Proto's available.

    // TODO udp and icmp are not tested

    public function create($type, $protocol) {
        $type_table = array(
            'inet'  => AF_INET, // FUTURE auto-select ipv4/ipv6 based on later events
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
            cout(osocket_debug, 'socket_create() has failed');

            return;
        } else {
            cout(osocket_debug, 'socket_create() success');
            $this->set_nonblocking();
        }
    }

    public function bind($src_ip) {
        // TODO can't call bind() when already connected
        if(($socket_bind = @socket_bind($this->socket, $src_ip)) < 0) {
            cout(osocket_debug, 'socket_bind() has failed');

            return false;
        } else {
            return true;
        }
    }

    final public function cycle() {
        $this->socket_poll();
        $this->on_cycle();
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
    public function set_blocking() {
        $this->is_blocking = true;

        return socket_set_block($this->socket);
    }

    public function set_nonblocking() {
        $this->is_blocking = false;

        return socket_set_nonblock($this->socket);
    }

    public function is_blocking() {
        return $this->is_blocking;
    }

    public function is_nonblocking() {
        return !$this->is_blocking;
    }

    public function last_error($socket = null) {
        if($socket === null) {
            $socket = $this->socket;
        }
        $errno = socket_last_error($socket);
        $b = socket_strerror($errno);
        socket_clear_error($socket);

        return $b;
    }

    abstract public function on_cycle();

    abstract protected function socket_poll();

    abstract protected function on_send($data, $socket = null);

    abstract protected function on_recv($data, $socket = null);
}