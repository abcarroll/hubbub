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


\Hubbub\Utility::Sunrise(); // Bootstrap CLI

// This injects everything with everything.
// Something something .. Like this ..
require 'conf/bootstrap.php';
if(!empty($bootstrap)) {

    // Initialize
    foreach($bootstrap as $depName => $depCfg) {
        $$depName = new $depCfg['object']();
        echo " > Bootstrapping $depName as {$depCfg['object']}\n";
    }

    // and Inject
    foreach($bootstrap as $depName => $depCfg) {
        foreach($depCfg['inject'] as $depInj) {
            $$depName->{'set' . $depInj}($$depInj);
            echo " >  Injecting $depInj into $depName\n";
        }
    }

} else {
    die("Missing bootstrap file!");
}

assert($conf instanceof \Hubbub\Configuration);
assert($logger instanceof \Hubbub\Logger);
assert($bus instanceof \Hubbub\MicroBus);

// Set the PHP Error Handler
$errorHandler = new ErrorHandler($logger);
$errorHandler->setHandler();

$hubbub = new Hubbub($conf, $logger, $bus, $throttler);
$hubbub->main();

\Hubbub\Utility::Sunset();
