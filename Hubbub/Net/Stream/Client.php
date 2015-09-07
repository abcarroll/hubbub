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

// In my code, this actually implements an interface, but for simplicity let's leave the interface out
class Client {
    private $parent; // Is the "parent" the correct term for this?
    private $socket;
    private $status = 'disconnected';

    public function __construct(\Hubbub\Net\Client $parent = null) { // in reality this would be a generic interface
        $this->setParent($parent);
    }

    public function setParent(\Hubbub\Net\Client $parent) {
        $this->parent = $parent;
    }

    public function connect($where) {
        if($this->status != 'disconnected') {
            echo " > connect() called while not disconnected...\n";
            return;
        }

        $this->parent->hubbub->logger->debug("Connecting to: $where");

        $errorNo = null;
        $errorStr = null;

        $this->status = 'connecting';
        $this->socket = @stream_socket_client($where, $errorNo, $errorStr, 15); //STREAM_CLIENT_ASYNC_CONNECT

        echo "past connection...";

        // Even in async mode, sometimes the socket will immediately return false?
        if($this->socket == false) {
            echo " > socket connection failed early.\n";
            $this->status = 'disconnected';
            $this->on_disconnect('connection-attempt-failed', $errorNo, $errorStr);
        } else {
            stream_set_blocking($this->socket, false);
        }
    }

    // Only use disconnect() if you wish to forcibly close the connection from our side
    public function disconnect() {
        $this->status = 'disconnected';
        @fclose($this->socket);
        $this->on_disconnect('disconnect-called');
    }

    public function send($data) {
        $result = fwrite($this->socket, $data);
        $this->on_send($data);
        return $result;
    }

    public function recv($length = 4096) {
        $data = fread($this->socket, $length);
        if(strlen($data) > 0) {
            $this->on_recv($data);
        }
    }

    public function iterate() {

        if($this->status != 'disconnected' && is_resource($this->socket) && !feof($this->socket)) {
            echo " > Iteration... \n";
            echo " > The socket looks to be in good condition...\n";

            // The socket was connecting but now is connected..
            if($this->status == 'connecting') {
                $this->status = 'connected';
                $this->on_connect();
            } else {
                $this->recv();
            }
        } else {
            if($this->status == 'connected') {
                echo " > due to circumstances outside our control, the socket was disconnected...\n";
                $this->status = 'disconnected';
                $this->on_disconnect();
            } elseif($this->status == 'connecting') { // for non-blocking sockets?
                echo " > the socket connection failed!\n";
                $this->status = 'disconnected';
                // i think we can still use socket_last_error() here
                $this->on_disconnect();
            } else {
                echo " > the socket is in a disconnected state...\n";
            }
        }
    }

    // Does this look too redundant?  Do you think this is best case?  This allows us to instead of injecting
    // this class/object into a protocol handler, we could actually instead just extend this class and override these methods
    // below.  Maybe even make sense to check for $this->parent being.. Or is this "wrong?"
    function on_connect() {
        if($this->parent != null) {
            $this->parent->on_connect();
        }
    }

    function on_disconnect($context = null, $errno = null, $errstr = null) {
        if($this->parent != null) {
            echo " > calling parent on_disconnect($context, $errno, $errstr)\n";
            $this->parent->on_disconnect($context, $errno, $errstr);
        }
    }

    function on_send($data) {
        if($this->parent != null) {
            echo " > calling parent on_send($data)\n";
            $this->parent->on_send($data);
        }
    }

    function on_recv($data) {
        if($this->parent != null) {
            echo " > calling parent on_recv($data)\n";
            $this->parent->on_recv($data);
        }
    }
}