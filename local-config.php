<?php
/* Example Configuration File */
$config = [
    'irc' => [
        'nickname' => 'HubTest-' . dechex(mt_rand(0, 255)),
        'username' => 'php',
        'realname' => 'Hubbub',
        'server'   => 'tcp://irc.freenode.net:6667',

        'timeout'  => 30
    ],

    'bnc' => [
        'listen'   => 'tcp://0.0.0.0:7777',
        'password' => '1234',
    ]
];