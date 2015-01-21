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
 * Class AdjustingDelay
 *
 * @package Hubbub\Throttler
 */
class AdjustingDelay extends Base {
    private $last_iteration_start = 0;
    /**
     * @param \Hubbub\Hubbub $hubbub    The hubbub object
     * @param int            $frequency Time in microseconds to sleep after work adjustment
     */
    function __construct($hubbub, $config) {
        parent::__construct($hubbub, $config);
        $this->frequency = $this->hubbub->config['throttler']['frequency'];
        $this->last_iteration_start = microtime(1);
    }

    /**
     * Throttles for 'frequency' sec minus how long the previous iteration lasted.
     */
    function throttle() {
        parent::throttle();

        $iteration_length = round((microtime(1) - $this->last_iteration_start) * 1000000);
        $iteration_sleep = $this->frequency - $iteration_length;
        if($iteration_sleep > 0) {
            usleep($iteration_sleep);
            $this->hubbub->logger->debug("[{$this->iteration}] Sleeping for $iteration_sleep uSec, iteration took $iteration_length uSec");
        } else {
            $this->hubbub->logger->debug("[{$this->iteration}] NOT Sleeping, iteration took $iteration_length uSec");
        }

        $this->last_iteration_start = microtime(1);
    }
}