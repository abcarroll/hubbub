<?php
// In my code, these are actually two separate files/classes:
// \MyApp\Net\Stream\Client (which implements \MyApp\Net\Generic\Client)
// \MyApp\Net\SomeProtocol\Client (which implements \MyApp\GenericProtocol\Client, or something another)


// In my code, this actually implements an interface, but for simplicity let's leave the interface out
class StreamClient {
    const SOCKET_DISCONNECTED = 0;
    const SOCKET_CONNECTED = 1;
    const SOCKET_CONNECTING = 2;

    // This is kinda trashy, but roughly what I think..
    const CONNNECTION_ATTEMPT_FAILED = 1;
    const OTHER_SIDE_CLOSED = 2;
    const DISCONNECT_CALLED = 3;

    private $socket;
    private $status = self::SOCKET_DISCONNECTED;
    private $parent; // Question #1, is the "parent" the correct term for this?

    public function __construct(TcpEchoClient $parent = null) { // in reality this would be a generic interface
        $this->parent = $parent;
    }

    public function setParent(TcpEchoClient $parent) {
        $this->parent = $parent;
    }

    public function connect($where) {
        if($this->status != self::SOCKET_DISCONNECTED) {
            echo " > connect() called while not disconnected...\n";

            return false;
        }

        $this->status = self::SOCKET_CONNECTING;
        $this->socket = @stream_socket_client($where, $errno, $errstr, STREAM_CLIENT_ASYNC_CONNECT);
        stream_set_blocking($this->socket, false);

        // Even in async mode, sometimes the socket will immediately return false?
        if($this->socket == false) {
            echo " > socket connection failed early.\n";
            $this->status = self::SOCKET_DISCONNECTED;
            $this->on_disconnect(self::CONNNECTION_ATTEMPT_FAILED, $errno, $errstr);
        }
    }

    // Only use disconnect() if you wish to forcibly close the connection from our side
    public function disconnect() {
        $this->status = self::SOCKET_DISCONNECTED;
        @fclose($this->socket);
        $this->on_disconnect(self::DISCONNECT_CALLED);
    }

    public function send($data) {
        $this->on_send($data);

        return fwrite($this->socket, $data);
    }

    public function recv($length = 4096) {
        $data = fread($this->socket, $length);
        if(strlen($data) > 0) {
            $this->on_recv($data);
        }
    }

    public function iterate() {

        if($this->status != self::SOCKET_DISCONNECTED && is_resource($this->socket) && !feof($this->socket)) {
            echo " > Iteration... \n";
            echo " > The socket looks to be in good condition...\n";

            // The socket was connecting but now is connected..
            if($this->status == self::SOCKET_CONNECTING) {
                $this->status = self::SOCKET_CONNECTED;
                $this->on_connect();
            } else {
                $this->recv();
            }
        } else {
            if($this->status == self::SOCKET_CONNECTED) {
                echo " > due to circumstances outside our control, the socket was disconnected...\n";
                $this->status = self::SOCKET_DISCONNECTED;
                $this->on_disconnect();
            } elseif($this->status == self::SOCKET_CONNECTING) { // for non-blocking sockets?
                echo " > the socket connection failed!\n";
                $this->status = self::SOCKET_DISCONNECTED;
                // i think we can still use socket_last_error() here
                $this->on_disconnect();
            } else {
                echo " > the socket is in a disconnected state...\n";
            }
        }
    }

    // Question #2: Does this look too redundant?  Do you think this is best case?  This allows us to instead of injecting
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

// This is our protocol handler, the idea being that we inject the network object above into the protocol handler
class TcpEchoClient {
    private $net;

    public $lastDataSent = null;

    public function __construct(StreamClient $net) { // in reality this would be a generic interface
        $this->net = $net;
        $this->net->connect('tcp://127.0.0.1:1234'); // I'll use netcat -l -p 1234 for the server..
    }

    public function iterate() {
        echo " > iterate!\n";
        $this->net->iterate();
    }

    public function on_connect() {
        $this->net->send("A client has connected, sir.\n");;
    }

    public function on_disconnect($context = null, $errno = null, $errstr = null) {
        echo " > Socket disconnected: $context, $errno, $errstr\n";
        exit;
    }

    public function on_send($data) {
        echo " > sent data: " . trim($data) . "\n";
        $this->lastDataSent = $data;
    }

    public function on_recv($data) {
        $this->net->send($data);
    }
}

$netClient = new StreamClient();
$protocolClient = new TcpEchoClient($netClient);
$netClient->setParent($protocolClient);

while (1) {
    $protocolClient->iterate();
    sleep(1);
}