<?php

/*
 * Dependency Injection Graph
 */

$bootstrap = [

    'conf'         => [
        'object' => '\Hubbub\Configuration',
        'inject' => [
            'logger', 'bus'
        ]
    ],

    'logger'       => [
        'object' => '\Hubbub\Logger',
        'inject' => [
            'conf', 'bus'
        ]
    ],

    'bus'          => [
        'object' => '\Hubbub\MicroBus',
        'inject' => [
            'conf', 'logger',
        ]
    ],

    'throttler'    => [
        'object' => '\Hubbub\Throttler\TimeAdjustedDelay',
        'inject' => [
            'conf', 'logger'
        ]
    ],
];