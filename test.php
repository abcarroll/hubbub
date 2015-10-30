<?php


$start = microtime(1);
$socket = stream_socket_client("tcp://127.0.0.1:6667", $errorNumber, $errorString, 1, STREAM_CLIENT_ASYNC_CONNECT);
$end = microtime(1);

printf("\n\nTime spent connecting: %f seconds\n\n", ($end - $start));


$data = new StdClass();
$data->a = "1";
$data->b = "2";

foreach($data as $bar) {
    echo "$bar\n";
}

