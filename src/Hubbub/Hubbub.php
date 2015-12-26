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

namespace Hubbub;

use Hubbub\Configuration;
use Hubbub\Iterator;
use Hubbub\Logger;
use Hubbub\Throttler\Throttler;

/**
 * Class Hubbub
 *
 * @package Hubbub
 */
class Hubbub {
    /**
     * @var \Dice\Dice
     */
    public $factory;

    /**
     * @var Configuration
     */
    public $conf;

    /**
     * @var Logger
     */
    public $logger;

    /**
     * @var \Hubbub\MessageBus
     */
    public $bus;

    public function __construct(\Dice\Dice $factory, Configuration $conf, MessageBus $bus, Iterator $iterator, Logger $logger) {
        $this->factory = $factory;
        $this->conf = $conf;
        $this->bus = $bus;
        $this->iterator = $iterator;
        $this->logger = $logger;

        /*
         * Subscribe our event handler
         */
        $this->bus->subscribe([$this, 'handleBusMessage'], [
            'protocol' => 'meta'
        ]);

        foreach($this->conf->get('hubbub') as $alias => $instanceOf) {
            $module = $this->createProtocol($instanceOf, $alias);
            $this->iterator->add($module, $alias);
        }

        $this->iterator->add($this->factory->create('\Hubbub\Throttler\Throttler'));
    }

    public function handleBusMessage($bus) {
        // TODO: Create / add objects to iterator based on bus messages
    }

    protected function createProtocol($class, $name = null) {
        $this->logger->debug("Creating new '$class' in createProtocol()");
        return $this->factory->create($class, [$name]);
    }

    /**
     * This function is called in the start / bootstrap code to loop for a very long time
     */
    public function loop() {
        $this->iterator->run();
    }
}