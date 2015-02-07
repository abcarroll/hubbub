<?php

/*
 * Example static config file: Will be switched out for something more dynamic eventually.
 * However this still may serve as a bootstrapping file.
 */

$conf = [
    'logger' => [
        'logToFile' => 'hubbub.log',
        'contextDumps' => true, // TODO Does not care about this setting
    ],

    'throttler'    => [
        'frequency' => 500000,
    ],

    'freenode' => [
        'class' => '\Hubbub\IRC\Client',
        'servers' => [

        ]
    ]
];