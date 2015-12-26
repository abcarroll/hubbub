#!/usr/bin/env php
<?php
/*
 * This file is a part of Hubbub, available at:
 * http://github.com/abcarroll/hubbub
 *
 * Copyright (c) 2013-2015, A.B. Carroll <ben@hl9.net>
 * Hubbub is distributed under a BSD-like license.
 *
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code, or available at the URL above.
 */

namespace Hubbub;

// PSR-4 autoloader
spl_autoload_register(function ($class) {
    $file = __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    if(file_exists($file)) {
        if(is_readable($file)) {
            require $file;
        } else {
            trigger_error("The file '$file' was not auto-loaded: It exists but is not readable.", E_USER_WARNING);
        }
    }
});


$main = function() {
    Bootstrapper::Sunrise();

    /**
     * @var \Dice\Dice $factory
     */

    $factory = Bootstrapper::getFactory();

    /**
     * @var \Hubbub\Hubbub $hubbub
     */

    $hubbub = $factory->create('\Hubbub\Hubbub', [$factory]);;
    $hubbub->loop();

    Bootstrapper::Sunset();
};

$main();