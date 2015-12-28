<?php

class TestServer {

    protected $server_socket;
    protected $client_sockets = array();

    public function __construct($location) {
        $errno = null;
        $errstr = null;

        $this->server_socket = stream_socket_server($location, $errno, $errstr);

        if(!$this->server_socket) {
            die("Failed to start a listening socket: [$errno]: $errstr");
        } else {
            echo "Listening on: $location\n";
        }

        echo "Beginning poll loop\n";
        $this->beginPoll();
    }

    protected function beginPoll() {
        $iterationCount = 0;
        while(true) {
            sleep(1);

            echo "Beginning iteration: " . (++$iterationCount) . "\n";
            $ready_sockets = [$this->server_socket] + $this->client_sockets;
            // resource &read, resource &write, resource &except, int tv_sec [, int tv_usec]
            $ready_count = stream_select($ready_sockets, $write, $except, 0, 0);

            foreach($ready_sockets as $socket) {
                // A client is connecting to our listening socket

                if($socket === $this->server_socket) {
                    echo "Accepting incoming socket connection...";
                    if(($client = stream_socket_accept($this->server_socket)) < 0) { // resource server_socket [, int timeout [, string &peername]]
                        trigger_error("Socket accept failed", E_USER_WARNING);
                    } else {
                        $this->client_sockets[(int) $client] = $client;
                        $this->on_client_connect($client);
                    }
                } else {
                    $data = $this->recv($socket);

                    // A client has disconnected from our listening socket
                    if(empty($data)) {
                        $this->on_client_disconnect($socket);
                        unset($this->client_sockets[(int) $socket]);
                    } else {
                        $this->on_client_recv($socket, $data);
                    }
                }
            }

        }
    }

    protected function recv($socket, $length = 8192) {
        return fread($socket, $length);

    }

    protected function on_client_connect($client) {
        echo "New incoming connection\n";
    }

    protected function on_client_disconnect($client) {
        echo "Client disconnection...\n";
    }

    protected function on_client_recv($client, $data) {
        echo "Received data from client: $data\n";
    }
}

$server = new TestServer("tcp://0.0.0.0:9999");