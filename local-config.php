<?php
/* Example Bootstrap File */
$config = [
    /*[
        'object'   => '\Hubbub\IRC\Client',
        'nickname' => 'HubTest-' . dechex(mt_rand(0, 255)),
        'username' => 'php',
        'realname' => 'Hubbub',
        'server'   => 'tcp://irc.freenode.net:6667',
        'timeout'  => 30
    ],
    [
        'listen'   => 'tcp://0.0.0.0:7777',
        'password' => '1234',
    ]*/

    'logger' => [
        'object' => '\Hubbub\Logger',
    ],

    'throttler' => [
        'object'    => '\Hubbub\Throttler\AdjustingDelay',
        'frequency' => 500000,
    ]

];