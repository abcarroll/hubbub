<?php
/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2015, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */

// See http://php.net/errorfunc.configuration.php#ini.error-reporting
error_reporting(2147483647);

// TODO Replace with SPL..
function autoload_hubbub($class) {
    if(is_file('lib/core/' . $class . '.php')) {
        require 'lib/core/' . $class . '.php';
    }
}

spl_autoload_register('autoload_hubbub');
spl_autoload_register(); // Register php-fig SPL Style autoloading

Utility::CheckEnv();

// Everything.
$h = new Hubbub();
$h->main();

echo date('r') . "\n";
echo "Hubbub gracefully shut down.\n";