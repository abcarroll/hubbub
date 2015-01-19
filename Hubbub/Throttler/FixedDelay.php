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
 * Class FixedDelay
 *
 * @package Hubbub\Throttler
 */
class FixedDelay extends Base {

    /**
     * Sleeps for a fixed amount of time regardless of work or load.
     */
    function throttle() {
        parent::throttle();
        //trigger_error("[{$this->iteration}] Sleeping for {$this->frequency}");
        usleep($this->frequency);
    }
}