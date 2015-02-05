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
class TimeAdjustedDelay extends Throttler {
    protected $conf, $logger;
    protected $frequency, $last_iteration_start = 0;

    /**
     * @param \Hubbub\Configuration $conf
     * @param \Hubbub\Logger        $logger
     */
    function __construct(\Hubbub\Configuration $conf = null, \Hubbub\Logger $logger = null) {
        parent::__construct($conf, $logger);
        $this->last_iteration_start = microtime(1);

        if($logger !== null)
            $this->setLogger($logger);

        if($conf !== null)
            $this->setConf($conf);
    }

    /**
     * Throttles for 'frequency' sec minus how long the previous iteration lasted.
     *
     * @todo Re-enable debugging messages when logging filtering is available.
     */
    function throttle() {
        parent::throttle();

        $iteration_length = round((microtime(1) - $this->last_iteration_start) * 1000000);
        $iteration_sleep = $this->frequency - $iteration_length;
        if($iteration_sleep > 0) {
            usleep($iteration_sleep);
            $this->logger->debug("[{$this->iteration}] Sleeping for $iteration_sleep uSec, iteration took $iteration_length uSec");
        } else {
            $this->logger->debug("[{$this->iteration}] NOT Sleeping, iteration took $iteration_length uSec");
        }

        $this->last_iteration_start = microtime(1);
    }

    public function setBus(\Hubbub\MicroBus $bus) {
        // @todo
    }

    public function setConf(\Hubbub\Configuration $conf) {
        $this->conf = $conf;
        $this->frequency = $this->conf['throttler']['frequency'];
    }

    public function setLogger(\Hubbub\Logger $logger) {
        $this->logger = $logger;
    }
}