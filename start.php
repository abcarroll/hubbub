#!/usr/bin/env php
<?php
/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2015, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */

namespace Hubbub;

// PSR-4 autoloader
spl_autoload_register(function ($class) {
    $file = __DIR__ . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    if(file_exists($file)) {
        require $file;
    }
});


\Hubbub\Bootstrap::Sunrise();

new Hubbub(Bootstrap::loadDependencies(
    Bootstrap::getDependenciesArray()
));

\Hubbub\Bootstrap::Sunset();
