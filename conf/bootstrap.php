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
            'conf', 'logger'
        ]
    ],

    'bus'       => [
        'class'  => '\Hubbub\MessageBus',
        'inject' => []
    ],

    'rootIterator' => [
        'class'  => '\Hubbub\RootIterator',
        'inject' => [
            'throttler', 'bus'
        ]
    ],
];
