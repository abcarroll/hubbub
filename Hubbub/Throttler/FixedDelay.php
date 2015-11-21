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