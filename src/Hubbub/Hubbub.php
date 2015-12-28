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

    /**
     * @var array
     * A queue of objects to create in the case that we're creating a very large amount of objects
     */
    protected $createQueue = [];

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

        // Add all the objects to the queue
        foreach($this->conf->get('hubbub') as $alias => $instanceOf) {
            $this->createQueue[] = [$instanceOf, $alias];
        } // and the throttler last, so that we can initialize without throttling$this->createQueue[] = ['\Hubbub\Throttler\Throttler', 'throttler'];
        $this->createQueue[] = ['\Hubbub\Throttler\Throttler', 'throttler'];
    }

    public function handleBusMessage($bus) {
        // TODO: Create / add objects to iterator based on bus messages
    }

    protected function newProtocol($class, $alias = null) {
        $this->logger->debug("Creating new '$class' in newProtocol()");
        $module = $this->factory->create($class, [$alias]);
        $this->iterator->add($module, $alias);
    }

    /**
     * This function is called in the start / bootstrap code to loop for a very long time
     */
    public function loop() {
        for(; ;) {
            if(count($this->createQueue) > 0) {
                $nextModule = array_shift($this->createQueue);
                $this->newProtocol($nextModule[0], $nextModule[1]);
            }

            $this->iterator->run();

        }
    }
}