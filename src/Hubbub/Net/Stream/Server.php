<?php
/*
 * This file is a part of Hubbub, available at:
 * http://github.com/abcarroll/hubbub
 *
 * Copyright (c) 2016, A.B. Carroll <ben@hl9.net>
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
    /**
     * The protocol handler where I/O events are sent
     * @var \Hubbub\Protocol\Server
     */
    protected $protocol;


    protected $location;

    /**
     * A string representation of the current transport being used in the listening socket, eg. 'tcp', 'udp', 'unix'
     * @var string
     */
    protected $transport;

    /**
     * A string representation of the address that is being listened on, eg. 0.0.0.0 or 192.168.0.15
     * @var string
     */
    protected $address;

    /**
     * An integer representation of the port that is being listened on, e.g 43123 or 6667
     * @var int
     */
    protected $port;

    /**
     * The listening server socket
     * @var
     */
    protected $serverSocket;

    /**
     * An array of all active client sockets
     * @var array
     */
    protected $clientSockets = [];


    /**
     * Set the protocol object in which will receive I/O events from the server and subsequent client connections.
     *
     * @param \Hubbub\Protocol\Server $protocol The protocol object to accept I/O events
     */
    public function setProtocol(\Hubbub\Protocol\Server $protocol) {
        $this->protocol = $protocol;
    }

    /**
     * Create a listening server socket.
     *
     * @param string $location Where and how to listen, eg. tcp://127.0.0.1:1234
     * @param string $flags    Which flags to pass directly to the stream socket formations.  Default 'auto' will select based on $location.
     *
     * @return bool Returns true if the socket binding was likely successful.  False if it has most definitely failed.
     */
    public function server($location, $flags = 'auto') {
        $transportToFlag = [
            'tcp'     => STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
            'udp'     => STREAM_SERVER_BIND,
            'unix'    => STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
            'udg'     => STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
            'ssl'     => STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
            'sslv3'   => STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
            'tls'     => STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
            'default' => STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
        ];

        $location_parsed = parse_url($location);

        if($flags === 'auto') { // auto select
            if(isset($transportToFlag[$location_parsed['scheme']])) {
                $flags = $transportToFlag[$location_parsed['scheme']];
            } else {
                trigger_error("Could not auto-select flags for transport '{$location_parsed['scheme']}', using defaults", E_USER_WARNING);
                $flags = $transportToFlag['default'];
            }
        }

        // Known Transports:
        // tcp, udp, unix, udg, ssl, sslv3, tls
        $this->serverSocket = stream_socket_server($location, $errorNumber, $errorStr, $flags);

        if($this->serverSocket === false) {
            trigger_error("Server socket creation failed: [$errorNumber] $errorStr", E_USER_WARNING);
        }

        return (bool) $this->serverSocket;
    }

    /**
     * Sends $data to the $clientId specified.
     *
     * @param int    $clientId The numeric clientId.
     * @param string $data     Arbitrary data to send over the socket.
     *
     * @return array|int
     */
    public function clientSend($clientId, $data) {
        $socket = $this->clientSockets[$clientId];
        $this->protocol->onClientSend($clientId, $data);

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

    /**
     * Forcefully disconnects the $clientId's connection to the server.
     *
     * @param int $clientId The numeric clientId.
     */
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
        $this->protocol->onClientDisconnect($clientId);
        unset($this->clientSockets[$clientId]);
        if(is_resource($socket)) {
            fclose($socket);
        }
        //$this->logger->debug("Client disconnected from clientId# $clientId");
    }

    /**
     * Toggle the socket's non-blocking/blocking behaviour.
     *
     * @param bool $blocking True to specify the socket as blocking, false to specify the socket as non-blocking.
     */
    public function setBlocking($blocking) {
        stream_set_blocking($this->serverSocket, $blocking);
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

        if(is_resource($this->serverSocket)) {
            $ready_sockets = [$this->serverSocket] + $this->clientSockets;
            stream_select($ready_sockets, $write, $except, 0, 0); // resource &read, resource &write, resource &except, int tv_sec [, int tv_usec]

            foreach($ready_sockets as $socket) {
                // A client is connecting to our listening socket
                if($socket === $this->serverSocket) {
                    if(($client = stream_socket_accept($this->serverSocket)) < 0) { // resource server_socket [, int timeout [, string &peername]]
                        //$this->logger->alert("socket_accept() has failed!");
                    } else {
                        $clientId = (int) $client;
                        //$this->logger->debug("New client socket accepted, clientId# $clientId");
                        $this->clientSockets[$clientId] = $client;
                        $this->protocol->onClientConnect($clientId);
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
                        $this->protocol->onClientRecv($clientId, $data);
                    }
                }
            }
        }
    }

    public function iterate() {
        $this->pollSockets();
    }
}