#!/usr/bin/env php
<?php
/*
 * This file is a part of Hubbub, available at:
 * http://github.com/abcarroll/hubbub
 *
 * Copyright (c) 2015-2015, A.B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */

namespace Hubbub;

// PSR-4 Autoloader
require(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Hubbub' . DIRECTORY_SEPARATOR . 'Autoloader.php');

Bootstrapper::Sunrise();
new Hubbub(Bootstrapper::loadDependencies(
    Bootstrapper::getDependenciesArray()
));
Bootstrapper::Sunset();
