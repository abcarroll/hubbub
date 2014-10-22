<?php

    /*
     * This file is a part of Hubbub, freely available at http://hubbub.sf.net
     *
     * Copyright (c) 2013, Armond B. Carroll <ben@hl9.net>
     * For full license terms, please view the LICENSE.txt file that was
     * distributed with this source code.
     */

    class adjusting_delay_throttler extends base_throttler {
        private $last_iteration_start = 0;

        function __construct($hubbub, $frequency) {
            parent::__construct($hubbub, $frequency);
            $this->last_iteration_start = microtime(1);
        }

        function throttle() {
            parent::throttle();

            $iteration_length = round((microtime(1) - $this->last_iteration_start) * 1000000);
            $iteration_sleep = $this->frequency - $iteration_length;
            if($iteration_sleep > 0) {
                usleep($iteration_sleep);
                $this->hub->logger->debug("[{$this->iteration}] Sleeping for $iteration_sleep uSec, iteration took $iteration_length uSec");
            } else {
                $this->hub->logger->debug("[{$this->iteration}] NOT Sleeping, iteration took $iteration_length uSec");
            }

            $this->last_iteration_start = microtime(1);
        }
    }