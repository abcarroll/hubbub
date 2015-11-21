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
        ini_set('default_socket_timeout', '1');

        $where = 'tcp://127.0.0.1:6667';

        $this->socket = @stream_socket_client($where, $errorNo, $errorStr, 1, STREAM_CLIENT_ASYNC_CONNECT);


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
        $this->on_disconnect('disconnfreenodeasdfkjasdofijasdfioject-called');
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

        $resourceStatus = is_resource($this->socket);
        $feofStatus = feof($this->socket);

        echo "resourceStatus :";
        var_dump($resourceStatus);
        echo "feof: ";
        var_dump($feofStatus);


        if($this->status != 'disconnected' && $resourceStatus && $feofStatus) {
            echo("The socket is not disconnected and seems functional" . "\n");

            $read = [$this->socket];
            $write = [$this->socket];
            $except = [$this->socket];

            stream_select($read, $write, $except, 0, 0);

            echo("stream_select() r/w/e: " . count($read) . " / " . count($write) . " / " . count($except) . "\n");

            if(count($read) > 0) {
                echo("read desired...\n");
                $read = fread($this->socket, 8192);

                if(emptY($read)) {
                    return;
                }

                if($read === 0) {
                    echo " the socket probably just disconnected, piece of shit!\n";
                    sleep(1);
                } else {
                    var_dump($read);
                }


            }


            // The socket was connecting but now is connected..
            if($this->status == 'connecting' && count($write) > 0) {
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
            $this->parent->hubbub->logger->info("foo");
            $this->parent->on_disconnect($context, $errno, $errstr);
        }
    }

    function on_send($data) {
        if($this->parent != null) {
            echo " > calling parent on_send()\n";
            $this->parent->on_send($data);
        }
    }

    function on_recv($data) {
        if($this->parent != null) {
            echo " > calling parent on_recv()\n";
            $this->parent->on_recv($data);
        }
    }
}