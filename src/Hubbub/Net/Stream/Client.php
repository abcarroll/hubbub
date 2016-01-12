<?php

/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2015, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */

/**
 * Transient version is available here:
 * https://gist.github.com/abcarroll/c6f4bf75870ceb4d9964
 */

namespace Hubbub\Net\Stream;

/**
 * Class Client
 * @package Hubbub\Net\Stream
 */
class Client implements \Hubbub\Net\Client {
    /** @var \Hubbub\Protocol\Client */
    private $protocol; // Event handler
    private $socket;
    private $socketState = 'disconnected';

    protected $logger;
    public function __construct(\Hubbub\Logger $logger) { // in reality this would be a generic interface
        $this->logger = $logger;
    }

    public function setProtocol(\Hubbub\Protocol\Client $parent) {
        $this->protocol = $parent;
    }

    public function set_blocking($mode) {
        // TODO: Implement set_blocking() method.
    }

    public function connect($where) {
        if($this->socketState != 'disconnected') {
            // TODO implement the ability to connect to a different server while already connected
            $this->logger->warning("connect() called while not disconnected");
            return;
        }

        $errorNo = null;
        $errorStr = null;
        $this->setSocketState('connecting');

        /*
         * @todo There is a bug, or at least unexpected behavior here where this call can take a VERY long time during the DNS lookup.  Fix me.
         *
         * So far, I have not really enjoyed the stream_* functions and in fact, don't particularly see the advantage over socket functions except the fact
         * that socket_* functions have to be explicitly added to compile-time config of PHP, and stream_* are built-in and non-removable.  stream_* functions
         * otherwise seem to me, A.B. Carroll, a step in the wrong direction otherwise, as there are numerous bugs regarding asynchronous connections and not
         * enough low level access is given to the blanket over the C wrapper libraries.
         *
         * So BE WARNED!  The function below is NOT truly async / non-blocking; the underlying networking will be, however the initial DNS lookup is liable
         * to hang indefinitely for all we know.
         */

        //$where = 'tcp://130.239.18.119:6667';
        ini_set('default_socket_timeout', '1');
        $this->logger->debug("Connecting to: $where");
        $this->socket = @stream_socket_client($where, $errorNo, $errorStr, 1, STREAM_CLIENT_ASYNC_CONNECT);

        /*
         * @todo Under what circumstance can this occur?
         */
        if($this->socket == false) {
            $this->setSocketState('disconnected');
            $this->on_disconnect('connection-attempt-failed', $errorNo, $errorStr);
        } else {
            stream_set_blocking($this->socket, false);
        }
    }

    // Only use disconnect() if you wish to forcibly close the connection from our side
    public function disconnect() {
        @fclose($this->socket);
        $this->setSocketState('disconnected');
        $this->on_disconnect('disconnect-called');
    }

    public function send($data) {
        $result = fwrite($this->socket, $data);
        $this->on_send($data);

        return $result;
    }

    public function recv($length = 1073741824) {
        $data = fread($this->socket, $length);
        if(strlen($data) > 0) {
            $this->on_recv($data);
        }

        return $data;
    }

    protected function setSocketState($socketState) {
        $this->logger->notice("Socket state change: $socketState");
        $this->socketState = $socketState;
    }

    public function iterate() {
        $resourceStatus = is_resource($this->socket);

        if($this->socketState != 'disconnected' && $resourceStatus) {
            $read = [$this->socket];
            $write = [$this->socket];
            $except = [$this->socket];

            stream_select($read, $write, $except, 0, 0);
            //$this->logger->debug("stream_select() r/w/e: " . count($read) . " / " . count($write) . " / " . count($except));

            if(count($read) > 0) {
                $recvData = $this->recv();
                if(strlen($recvData) == 0) {
                    $this->logger->debug("Empty recvData: Assuming socket disconnection");
                    $this->disconnect();
                }
            }

            if($this->socketState == 'connecting' && (count($write) > 0)) { // || count($read) > 0
                $this->logger->debug("Read/Write sockets available: assuming connection success");
                $this->setSocketState('connected');
                $this->on_connect();
            }
        } else {
            if($this->socketState == 'connected') { // resource status is false
                $this->logger->debug("Socket resource is false while CONNECTED");
                $this->logger->debug("Due to circumstances outside our control, the socket was disconnected...");
                $this->disconnect();
            } elseif($this->socketState == 'connecting') { // for non-blocking sockets
                $this->logger->debug("Socket resource is false while CONNECTING");
                $this->disconnect();
            } else {
                // $this->logger->debug("The socket is in a disconnected state...");
            }
        }
    }

    // Does this look too redundant?  Do you think this is best case?  This allows us to instead of injecting
    // this class/object into a protocol handler, we could actually instead just extend this class and override these methods
    // below.  Maybe even make sense to check for $this->parent being.. Or is this "wrong?"
    function on_connect() {
        if($this->protocol != null) {
            $this->protocol->on_connect();
        }
    }

    function on_disconnect($context = null, $errno = null, $errstr = null) {
        if($this->protocol != null) {
            $this->logger->debug(" > calling parent on_disconnect($context, $errno, $errstr)");
            $this->protocol->on_disconnect($context, $errno, $errstr);
        }
    }

    function on_send($data) {
        if($this->protocol != null) {
            //$this->logger->debug(" > calling parent on_send()");
            $this->protocol->on_send($data);
        }
    }

    function on_recv($data) {
        if($this->protocol != null) {
            //$this->logger->debug(" > calling parent on_recv()");
            $this->protocol->on_recv($data);
        }
    }
}