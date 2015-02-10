<?php
/*
 * Dependency Injection Graph
 */

return [
    'conf'         => [
        'class'  => '\Hubbub\Configuration',
        'inject' => [
        ]
    ],
    'logger'       => [
        'class'  => '\Hubbub\Logger',
        'inject' => [
            'conf',
        ]
    ],
    'errorHandler' => [
        'class'  => '\Hubbub\ErrorHandler',
        'inject' => [
            'logger'
        ]
    ],
    'moduleIterator' => [
        'class' => '\Hubbub\ModuleIterator'
    ],

    'bus'          => [
        'class'  => '\Hubbub\MicroBus',
        'inject' => [
        ]
    ],
    'throttler'    => [
        'class'  => '\Hubbub\Throttler\TimeAdjustedDelay',
        'inject' => [
            'conf',
            'logger'
        ]
    ]
];
