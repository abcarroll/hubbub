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
 * Class AdjustingDelay
 *
 * @package Hubbub\Throttler
 */
class TimeAdjustedDelay extends Throttler implements Iterable {
    protected $conf, $logger;
    protected $frequency, $last_iteration_start = 0;

    /**
     * @param \Hubbub\Configuration $conf
     * @param \Hubbub\Logger        $logger
     */
    function __construct(\Hubbub\Configuration $conf = null, \Hubbub\Logger $logger = null) {
        parent::__construct($conf, $logger);
        $this->last_iteration_start = microtime(1);

        if($logger !== null) {
            $this->setLogger($logger);
        }

        if($conf !== null) {
            $this->setConf($conf);
        }
    }

    protected $totalRunTime = 0;

    /**
     * Throttles for 'frequency' sec minus how long the previous iteration lasted.
     *
     * @todo Re-enable debugging messages when logging filtering is available.
     */
    function iterate() {
        parent::throttle();



        $iteration_length = round((microtime(1) - $this->last_iteration_start) * 1000000);


        $this->totalRunTime += $iteration_length;

        $iteration_sleep = $this->frequency - $iteration_length;
        if($iteration_sleep > 0) {
            usleep($iteration_sleep);
            // $this->logger->debug("[{$this->iteration}] Sleeping for $iteration_sleep uSec, iteration took $iteration_length uSec");
        } else {
            $this->logger->warning("[{$this->iteration}] NOT Sleeping, iteration took $iteration_length uSec");
        }

        $this->last_iteration_start = microtime(1);
    }

    public function setConf(\Hubbub\Configuration $conf) {
        $this->conf = $conf;
        $this->frequency = $this->conf->get('throttler/frequency');
    }

    public function setLogger(\Hubbub\Logger $logger) {
        $this->logger = $logger;
    }
}