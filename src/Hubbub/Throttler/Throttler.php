<?php
/*
 * This file is a part of Hubbub, available at:
 * http://github.com/abcarroll/hubbub
 *
 * Copyright (c) 2015, A.B. Carroll <ben@hl9.net>
 * Hubbub is distributed under a BSD-like license.
 *
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code, or available at the URL above.
 */

namespace Hubbub\Throttler;

use Hubbub\Iterable;

/**
 * Class Throttler
 * @package Hubbub\Throttler
 * @todo    I'm not 100% sure this class is necessary as it does very little.  Perhaps an interface would be more appropriate?
 */
abstract class Throttler implements Iterable {
    protected $conf, $logger, $iteration;

    /**
     * @param \Hubbub\Configuration|null $conf   The configuration object
     * @param \Hubbub\Logger|null        $logger The logger object
     */
    public function __construct(\Hubbub\Configuration $conf = null, \Hubbub\Logger $logger = null) {
        $this->conf = $conf;
        $this->logger = $logger;
    }

    /**
     * Increments the iteration counter
     */
    function throttle() {
        $this->iteration++;
    }
}