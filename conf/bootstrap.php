<?php
/*
 * Dependency Injection Graph
 */

return [
    'conf'         => [
        'class'  => '\Hubbub\Configuration',
        'inject' => [
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