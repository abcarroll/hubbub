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

/* Warning: broken */

namespace Hubbub\Net\Socket;

    /**
     * This is a generic server implementation using PHP's wrapper around Berkley sockets.
     * It is marked as broken.  When this was moved, there was another blank implementation that needs to be removed.
     *
     * @todo Test & fix this implementation.
     */

/**
 * Class Server
 *
 * @package Hubbub\Net\Socket
 */
class Server { // TODO implements Net Generic Server
    private $address, $port;
    private $socket_err_constant = E_USER_ERROR;

    public $client_sockets = [];
    public $listen_socket;

    /**
     * @param $address
     * @param $port
     */
    function __construct($address, $port) {
        $this->address = $address;
        $this->port = $port;

        if(($this->listen_socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) < 0) {
            trigger_error("socket_create() failed: " . socket_strerror($this->listen_socket), $this->socket_err_constant);

            return;
        }

        if(($ret = socket_bind($this->listen_socket, $this->address, $this->port)) < 0) {
            trigger_error("socket_bind() failed: " . socket_strerror($this->listen_socket), $this->socket_err_constant);

            return;
        }

        if(($ret = socket_listen($this->listen_socket, SOMAXCONN)) < 0) {
            trigger_error("socket_listen() failed: " . socket_strerror($ret), $this->socket_err_constant);

            return;
        }

        trigger_error("I am now listening on $port");
    }

    /**
     * @param $socket
     * @param $data
     *
     * @return int
     */
    function send($socket, $data) {
        if(is_array($socket)) {
            $this->on_send($socket, $data);
            foreach($socket as $s) {
                return socket_write($s, $data);
            }
        } else {
            $this->on_send(array($socket), $data);

            return socket_write($socket, $data);
        }
    }


    function iterate() {
        $updated_sockets = $this->listen_socket + $this->client_sockets;
        $num_updated_sockets = socket_select($updated_sockets, $write, $except, 0, 0);

        foreach($updated_sockets as $socket) {
            // A client connecting to one of our listening sockets
            if(in_array($socket, $this->server_sockets)) {
                if(($client = socket_accept($socket)) < 0) {
                    trigger_error("socket_accept() failed: " . socket_strerror($sock), $this->socket_err_constant);
                    continue;
                } else {
                    $this->socktable_add($client, 'server-client', $this->socktable_lookup($socket, 3));
                }
            } else {
                // Data is being received or disconnection
                // TODO should this be queued? (receiving data)
                // I don't see how it could be particularly beneficial nor how
                // to prioritize the traffic, so I don't think so, but worth a long think
                $bytes = socket_recv($socket, $buffer, 4096, 0);

                if($bytes == 0) {
                    // There has been a disconnection
                    #unset($this->client_sockets[
                    socket_close($socket);
                } else {
                    // Real data is being received
                    if($this->all_sockets_lookup[(int) $socket] == 'bnc') {
                        $this->bnc->on_recv($socket, $buffer);
                    }
                }
            }
        }
    }

    // ---- The rest are 'trigger' or 'callback' functions that are meant to be overridden by our
    // children
    function on_cycle($cycle) {
        echo "[ cycle ]\n";
    }

    function on_client_connect($socket) {
        socket_getpeername($socket, $addr, $port);
        echo "[RECV<=] Client connecting $addr:$port\n";
    }

    function on_client_disconnect($socket) {
        socket_getpeername($socket, $addr, $port);
        echo "[RECV<=] Client disconnecting $addr:$port\n";
    }

    function on_recv($socket, $data) {
        socket_getpeername($socket, $addr, $port);
        echo "[RECV<=] Client $addr:$port recv data:\n";
        echo "[ -DATA] " . trim($data) . "\n";
    }

    function on_send($sockets, $data) {
        foreach($sockets as $s) {
            socket_getpeername($s, $addr, $port);
            echo "[SEND=>] Client $addr:$port send data:\n";
            echo "[ -DATA] " . trim($data) . "\n";
        }
    }
}
