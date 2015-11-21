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

namespace Hubbub\Net\Stream;

/**
 * Class Server
 *
 * @package Hubbub\Net\Stream
 */
abstract class Server implements \Hubbub\Net\Generic\Server {
    private $location, $transport, $address, $port;

    private $guess_server_flags = [];

    private $server_socket = [];
    private $client_sockets = [];

    public function __construct($location) {
        $this->guess_server_flags = [
            'tcp'     => STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
            'udp'     => STREAM_SERVER_BIND,
            'unix'    => STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
            'udg'     => STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
            'ssl'     => STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
            'sslv3'   => STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
            'tls'     => STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
            'default' => STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
        ];

        $this->server($location);
    }

    public function server($location, $flags = 'auto') {
        $location_parsed = parse_url($location);

        if($flags == 'auto') { // auto select
            if(isset($this->guess_server_flags[$location_parsed['scheme']])) {
                $flags = $this->guess_server_flags[$location_parsed['scheme']];
            } else {
                trigger_error("Could not autoselect flags for transport '{$location_parsed['scheme']}', using defaults", E_USER_WARNING);
                $flags = $this->guess_server_flags['default'];
            }
        }

        // Known Transports:
        // tcp, udp, unix, udg, ssl, sslv3, tls

        $this->server_socket = stream_socket_server($location, $errno, $errstr);

        if($this->server_socket === false) {
            trigger_error("Server socket creation failed: [$errno] $errstr", E_USER_WARNING);
        }
    }

    public function send($socket, $data) {
        //$data = $this->on_client_send($socket, $data);
        $this->on_client_send($socket, $data);

        if(is_array($socket)) {
            $ret = [];
            foreach($socket as $s) {
                $ret[] = stream_socket_sendto($socket, $data);
            }
        } else {
            $ret = stream_socket_sendto($socket, $data);
        }

        return $ret;
    }

    public function recv($socket, $length = 8192) {
        return fread($socket, $length);
    }

    public function poll_sockets() {
        $ready_sockets = [$this->server_socket] + $this->client_sockets;
        $ready_count = stream_select($ready_sockets, $write, $except, 0, 0); // resource &read, resource &write, resource &except, int tv_sec [, int tv_usec]

        foreach($ready_sockets as $socket) {
            // A client is connecting to our listening socket
            if($socket === $this->server_socket) {
                if(($client = stream_socket_accept($this->server_socket)) < 0) { // resource server_socket [, int timeout [, string &peername]]
                    trigger_error("Socket accept failed", E_USER_WARNING);
                } else {
                    $this->client_sockets[(int) $client] = $client;
                    $this->on_client_connect($client);
                }
            } else {
                $data = $this->recv($socket);

                // A client has disconnected from our listening socket
                if($data === 0) {
                    $this->on_client_disconnect($socket);
                    unset($this->client_sockets[(int) $socket]);
                } else {
                    $this->on_client_recv($socket, $data);
                }
            }
        }
    }

    public function set_blocking($blocking) {
        stream_set_blocking($this->server_socket, $blocking);
    }

    public function iterate() {
        $this->on_iterate();
        $this->poll_sockets();
    }

    // Callbacks
    abstract public function on_client_connect($socket);

    abstract public function on_client_disconnect($socket);

    abstract public function on_client_recv($socket, $data);

    abstract public function on_client_send($socket, $data);

    abstract public function on_iterate();
}