<?php
/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2015, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */

namespace Hubbub;

// Auto-loading "bootstrap"
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

// $conf = new Configuration();

// Set the PHP Error Handler
ErrorHandler::setErrorHandler();
\Hubbub\Utility::Sunrise(); // Bootstrap CLI

// Something something .. Like this ..
//$logger = new Logger();
//ErrorHandler::setErrorHandler($logger);
//$conf->setLogger($logger);

$h = new Hubbub();
$bootstrap = new \Hubbub\Configuration($h);
$h->addModules($bootstrap->data);
$h->main();

\Hubbub\Utility::Sunset();
