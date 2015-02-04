<?php

/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2013, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */

namespace Hubbub\Throttler;

/**
 * Class Base
 * I'm not 100% sure this class is necessary as it does very little.  Perhaps an interface would be more appropriate?
 *
 * @package Hubbub\Throttler
 */
class Base {
    protected $hubbub, $conf, $frequency, $iteration;

    function __construct(\Hubbub\Hubbub $hubbub, Array $conf) {
        $this->hubbub = $hubbub;
        $this->conf = $conf;
    }

    /**
     * Increments the iteration counter
     */
    function throttle() {
        $this->iteration++;
    }
}