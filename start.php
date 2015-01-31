<?php
/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2015, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */

namespace Hubbub;

// Auto-loading
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

// Handling "Normal" PHP Errors.  Closely resembles the example on set_error_handler() docs.
//function Hubbub_handlePhpErrors($errno, $errstr , $errfile , $errline , $errcontext) {
set_error_handler(function ($errno, $errstr , $errfile , $errline , $errcontext) {
		// This error code is not included in error_reporting
    if (!(error_reporting() & $errno)) {
        return;
    }

    switch($errno) {
        case E_USER_ERROR:
        case E_RECOVERABLE_ERROR:
            // Should get the logger and call $logger->error()
            throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);

        default:
            // Just let the default PHP error handling work for now.
            // @todo This should call the appropriate PsrLogger method for all types.
            return false;
    }
});

// Everything.
\Hubbub\Utility::Sunrise();

$h = new Hubbub();
$bootstrap = new \Hubbub\Configuration($h);
$h->addModules($bootstrap->data);
$h->main();

\Hubbub\Utility::Sunset();
