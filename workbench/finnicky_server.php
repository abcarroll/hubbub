<?php
// TODO Replace with SPL..
function autoload_hubbub($class) {
    require 'lib/core/' . $class . '.php';
}

spl_autoload_register('autoload_hubbub');
spl_autoload_register(); // Register php-fig SPL Style autoloading

class Finnicky extends net_stream_server {
    function on_client_connect($client) {
        echo "Client Connected\n";
    }

    function on_client_disconnect($client) {
        echo "Client Disconnected\n";
    }

    function on_client_recv($socket, $data) {
        if(!empty($data)) {
            echo "Recv'd $data\n";
        }
    }

    function on_client_send($socket, $data) {
        echo "Send $data through appropriate interface\n";
    }

    function on_iterate() {
        echo ".";
        sleep(10);
    }

}

$server = new Finnicky("tcp://127.0.0.1:9881");
while (1) {
    $server->iterate();
}

