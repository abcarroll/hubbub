<?php

/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2013, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */

class fixed_delay_throttler extends base_throttler {
    function throttle() {
        parent::throttle();
        trigger_error("[{$this->iteration}] Sleeping for {$this->frequency}");
        usleep($this->frequency);
    }
}