<?php
/* Example Bootstrap File */
$config = [
    /*
     * Internal Modules
     */
    'logger'    => [
        'object'    => '\Hubbub\Logger',
        'logToFile' => 'hubbub.log',
    ],

    'throttler' => [
        'object'    => '\Hubbub\Throttler\AdjustingDelay',
        'frequency' => 500000,
    ],
    'bus' => [
        'object' => '\Hubbub\MicroBus'
    ],

    /*
     * Configure these, maybe...
     */
    [
        'object' => '\Hubbub\IRC\Bnc',
        'listen' => 'tcp://0.0.0.0:7777',
        'users' => [
            'corndog' => 'myPass'
        ]
    ], [
        'object'   => '\Hubbub\IRC\Client',
        'network'  => 'freenode',
        'nickname' => 'HubTest-' . dechex(mt_rand(0, 255)),
        'username' => 'php',
        'realname' => 'Hubbub',
        'server'   => 'tcp://irc.freenode.net:6667',
        'timeout'  => 30
    ],
];