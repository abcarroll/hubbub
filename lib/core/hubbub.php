<?php

/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2013, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */

class hubbub {
    public $logger, $throttler, $modules;

    /**
     * Initiates a new hubbub object.  Meant to be called once, to start an isolated instance.
     */
    public function __construct() {
        $this->logger = new logger($this);
        $this->throttler = new adjusting_delay_throttler($this, 500000);
    }

    /**
     * The main loop. This iterates over all the root modules and runs the injected throttler.
     */
    public function main() {

        $this->modules[] = new bnc($this);
        $this->modules[] = new irc_client($this);

        while (1) {
            if(count($this->modules) > 0) {
                foreach ($this->modules as $m) {
                    $m->iterate();
                }
            }
            $this->throttler->throttle();
        }
    }

}