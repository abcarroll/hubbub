<?php
/*
 * Dependency Injection Graph
 * Under normal circumstance it is not necessary to edit this file.
 */

return [
    'conf'           => [
        'class'  => '\Hubbub\Configuration',
    ],

    'logger'         => [
        'class'  => '\Hubbub\Logger',
        'inject' => [
            'conf'
        ]
    ],

    'errorHandler'   => [
        'class'  => '\Hubbub\ErrorHandler',
        'inject' => [
            'logger'
        ]
    ],

    'throttler'    => [
        'class'  => '\Hubbub\Throttler\TimeAdjustedDelay',
        'inject' => [
            'logger', 'conf'
        ]
    ],

    'bus'       => [
        'class'  => '\Hubbub\MessageBus',
        'inject' => []
    ],

    'iterator' => [
        'class'  => '\Hubbub\Iterator',
        'inject' => [
            'logger', 'throttler', 'bus'
        ]
    ],
];
