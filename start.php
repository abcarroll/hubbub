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
    if(file_exists($file)) {
        require $file;
    }
});

\Hubbub\Bootstrap::Sunrise();
$dependencies = Bootstrap::loadDependencies();
$hubbub = new Hubbub(
    $dependencies['conf'],
    $dependencies['logger'],
    $dependencies['bus'],
    $dependencies['throttler']
);
$hubbub->main();
\Hubbub\Bootstrap::Sunset();
