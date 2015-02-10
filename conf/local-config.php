<?php
/*
 * Example static config file: Will be switched out for something more dynamic eventually.
 */

$conf = [
    'logger' => [
        'logToFile' => 'hubbub.log',
        'contextDumps' => true, // TODO Does not care about this setting
    ],
    'throttler' => [
        'frequency' => 500000,
    ]
];