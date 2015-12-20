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
    /** @var Configuration */
    public $conf;
    /** @var Logger */
    public $logger;
    /** @var \Hubbub\MessageBus */
    public $bus;
    /** @var Throttler */
    public $throttler;
    /** @var Iterator */
    public $iterator;

    /** @var \Dice\Dice */
    public $factory;

    public function __construct(\Dice\Dice $factory, Configuration $conf, MessageBus $bus, Iterator $iterator, Logger $logger) {
        $this->factory = $factory;
        $this->conf = $conf;
        $this->iterator = $iterator;
        $this->bus = $bus;
        $this->logger = $logger;

        $this->init();
        $this->run();
    }

    protected function createProtocol($class, $name = null) {
        $this->logger->debug("Creating new '$class' in createProtocol()");
        return $this->factory->create($class, [$name]);
    }

    public function handleBusMessage($bus) {
        //$this->createProtocol($bus[])
    }

    public function init() {
        $this->bus->subscribe([$this, 'handleBusMessage'], [
            'protocol' => 'meta'
        ]);

        foreach($this->conf->get('hubbub') as $alias => $init) {
            $new = $this->createProtocol($init['class'], $init['conf']);
            $this->iterator->add($new, $alias);
        }

        $this->iterator->add($this->factory->create('\Hubbub\Throttler\Throttler'));
    }

    public function run() {
        $this->iterator->run();
    }
}