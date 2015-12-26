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

require __DIR__ . '/src' . '/autoload.php';

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