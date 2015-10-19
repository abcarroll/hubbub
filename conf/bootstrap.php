<?php
/*
 * Dependency Injection Tree
 * Under normal circumstance it is not necessary to edit this file.
 * You can however, edit this file to change core functionality, such as replace the throttler.
 *
 * Be careful, this loads the entire piece of software, including the Hubbub object itself.
 */

return [
    'conf'         => [
        'class' => '\Hubbub\Configuration',
    ],
    'logger'       => [
        'class'  => '\Hubbub\Logger',
        'inject' => [
            'conf'
        ]
    ],
    'errorHandler' => [
        'class'  => '\Hubbub\ErrorHandler',
        'inject' => [
            'logger'
        ],
    ],
    'bus'          => [
        'class'  => '\Hubbub\MessageBus',
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
    'iterator'     => [
        'class'  => '\Hubbub\Iterator',
        'inject' => [
            'logger',
            'bus',
            'throttler',
        ]
    ],
];
