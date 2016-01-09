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
class Server implements \Hubbub\Net\Server {
    protected $location, $transport, $address, $port;

    /**
     * @var \Hubbub\Protocol\Server
     */
    protected $protocol;
    protected $guess_server_flags = [
        'tcp'     => STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
        'udp'     => STREAM_SERVER_BIND,
        'unix'    => STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
        'udg'     => STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
        'ssl'     => STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
        'sslv3'   => STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
        'tls'     => STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
        'default' => STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
    ];

    protected $server_socket;
    protected $clientSockets = [];

    //protected $logger;

    public function __construct() { //\Hubbub\Logger $logger
        //$this->logger = $logger;
    }

    public function setProtocol(\Hubbub\Protocol\Server $protocol) {
        $this->protocol = $protocol;
    }

    public function server($location, $flags = 'auto') {
        $location_parsed = parse_url($location);

        if($flags == 'auto') { // auto select
            if(isset($this->guess_server_flags[$location_parsed['scheme']])) {
                $flags = $this->guess_server_flags[$location_parsed['scheme']];
            } else {
                trigger_error("Could not auto-select flags for transport '{$location_parsed['scheme']}', using defaults", E_USER_WARNING);
                $flags = $this->guess_server_flags['default'];
            }
        }

        // Known Transports:
        // tcp, udp, unix, udg, ssl, sslv3, tls
        $this->server_socket = stream_socket_server($location, $errno, $errstr, $flags);

        if($this->server_socket === false) {
            trigger_error("Server socket creation failed: [$errno] $errstr", E_USER_WARNING);
        }

        return (bool) $this->server_socket;
    }

    public function clientSend($clientId, $data) {
        $socket = $this->clientSockets[$clientId];
        $this->protocol->on_client_send($clientId, $data);

        if(is_array($socket)) {
            $ret = [];
            foreach($socket as $s) {
                $ret[] = stream_socket_sendto($s, $data);
            }
        } else {
            $ret = stream_socket_sendto($socket, $data);
        }

        return $ret;
    }

    public function clientDisconnect($clientId) {
        //$this->logger->debug("clientDisconnect() forcefully called for clientId# $clientId");
        $socket = $this->clientSockets[$clientId];
        $this->disconnectSocket($socket);
    }

    /**
     * Internal disconnection.  If not already disconnected, disconnect the socket.  Remove the socket from the lookup table and trigger the callback.
     *
     * @param resource $socket The socket resource to disconnect & cleanup
     */
    protected function disconnectSocket($socket) {
        $clientId = (int) $socket;
        $this->protocol->on_client_disconnect($clientId);
        unset($this->clientSockets[$clientId]);
        if(is_resource($socket)) {
            fclose($socket);
        }
        //$this->logger->debug("Client disconnected from clientId# $clientId");
    }

    public function set_blocking($blocking) {
        stream_set_blocking($this->server_socket, $blocking);
    }

    public function pollSockets() {
        // Forcefully disconnected sockets
        // I've been unable to figure out a way to handle this otherwise; this is caused from fclose()'ing a socket to forcefully disconnect it, then fclose()
        // instantly pushes it out of resource state into an integer
        foreacH($this->clientSockets as $clientId => $cSocket) {
            if(!is_resource($cSocket) || is_resource($cSocket) && feof($cSocket)) {
                //$this->logger->debug("Real socket no longer valid, forcefully disconnecting clientId# " . $clientId);
                $this->disconnectSocket($cSocket);
            }
        }

        if(is_resource($this->server_socket)) {
            $ready_sockets = [$this->server_socket] + $this->clientSockets;
            stream_select($ready_sockets, $write, $except, 0, 0); // resource &read, resource &write, resource &except, int tv_sec [, int tv_usec]

            foreach($ready_sockets as $socket) {
                // A client is connecting to our listening socket
                if($socket === $this->server_socket) {
                    if(($client = stream_socket_accept($this->server_socket)) < 0) { // resource server_socket [, int timeout [, string &peername]]
                        //$this->logger->alert("socket_accept() has failed!");
                    } else {
                        $clientId = (int) $client;
                        //$this->logger->debug("New client socket accepted, clientId# $clientId");
                        $this->clientSockets[$clientId] = $client;
                        $this->protocol->on_client_connect($clientId);
                    }
                } else {
                    $clientId = (int) $socket;
                    $data = fread($socket, 8192);

                    // A client has disconnected from our listening socket
                    if($data === 0) {
                        //$this->logger->debug("data === 0, disconnected clientId# $clientId");
                        $this->disconnectSocket($socket);
                    } else {
                        //$this->logger->debug("Data received from clientId# $clientId");
                        $this->protocol->on_client_recv($clientId, $data);
                    }
                }
            }
        }
    }

    public function iterate() {
        $this->pollSockets();
    }
}