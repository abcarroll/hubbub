<?php

/*
 * Dependency Injection Graph
 */

$bootstrap = [

    'conf'         => [
        'class'  => '\Hubbub\Configuration',
        'inject' => [
            'logger',
            'bus'
        ]
    ],
    'logger'       => [
        'class'  => '\Hubbub\Logger',
        'inject' => [
            'conf',
            'bus'
        ]
    ],
    'errorHandler' => [
        'class'  => '\Hubbub\ErrorHandler',
        'inject' => [
            'logger'
        ]
    ],
    'bus'          => [
        'class'  => '\Hubbub\MicroBus',
        'inject' => [
            'conf',
            'logger',
        ]
    ],
    'throttler'    => [
        'class'  => '\Hubbub\Throttler\TimeAdjustedDelay',
        'inject' => [
            'conf',
            'logger'
        ]
    ],
];