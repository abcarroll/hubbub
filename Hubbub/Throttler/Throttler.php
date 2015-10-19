<?php

/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2015, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */

namespace Hubbub\Throttler;

/**
 * Class Throttler
 * @package Hubbub\Throttler
 * @todo I'm not 100% sure this class is necessary as it does very little.  Perhaps an interface would be more appropriate?
 */
abstract class Throttler {
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