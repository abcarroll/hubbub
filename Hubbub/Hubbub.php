<?php

/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2015, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */

namespace Hubbub;
use \Hubbub\Throttler\AdjustingDelay;

/**
 * Class Hubbub
 *
 * @package Hubbub
 */
class Hubbub extends \StdClass { // TODO Not sure if StdClass is the way to do it here
    protected $modules = [];

    /**
     * Initiates a new hubbub object.  Meant to be called once, to start an isolated instance.
     */
    public function __construct() {
        // Not very sleek...
        $this->config = new Configuration($this);
        $this->config = $this->config->getData();

        $this->logger = new Logger($this);
        $this->throttler = new AdjustingDelay($this, 500000);
    }

    /**
     * The main loop. This iterates over all the root modules and runs the injected throttler.
     */
    public function main() {
        //$this->modules[] = new \Hubbub\IRC\Bnc($this);
        //$this->modules[] = new \Hubbub\IRC\Client($this);

        while (1) {
            if(count($this->modules) > 0) {
                /** @var $m \Hubbub\Net\Generic\Client */
                foreach ($this->modules as $m) {
                    $m->iterate();
                }
            }
            $this->throttler->throttle();
        }
    }
}