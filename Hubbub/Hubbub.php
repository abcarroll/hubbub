<?php

/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2015, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */

namespace Hubbub;

/**
 * Class Hubbub
 *
 * @package Hubbub
 */
class Hubbub {
    /**
     * @var array                  $modules
     * @var \Hubbub\Throttler\Base $throttler
     * @var \Hubbub\Logger         $logger
     * @var \Hubbub\MicroBus       $bus
     */
    private $modules = [], $conf, $throttler, $logger, $bus;

    /**
     * Initiates a new hubbub object.  Meant to be called once, to start an isolated instance.
     */
    public function __construct(\Hubbub\Configuration $conf, \Hubbub\Logger $logger, \Hubbub\MicroBus $bus, \Hubbub\Throttler\Throttler $throttler) {
        $this->conf = $conf;
        $this->logger = $logger;
        $this->throttler = $throttler;
    }

    /**
     * The main loop. This iterates over all the root modules, calling their iterate() method, and runs the injected throttler.
     */
    public function main() {
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

    /**
     * Adds a new module onto the core Hubbub observer-iterator
     *
     * @param $conf
     *
     * @throws \Exception
     */
    public function addModules($conf) {
        $this->conf = $conf;

        foreach($conf as $mKey => $mVal) {
            if(!empty($mVal['object'])) {
                $object = new $mVal['object'](
                    $this->getConf(),
                    $this->getLogger(),
                    $this->getBus()
                );

                $this->modules[$mKey] = $object;
            } else {
                throw new \Exception("addModule() called with a missing 'object' \$config index");
            }
        }
    }

    /*
     * Getters & Setters
     */
    public function getConf() {
        return $this->conf;
    }

    public function setConf($conf) {
        $this->conf = $conf;
    }

    public function getLogger() {
        return $this->logger;
    }

    public function setLogger($logger) {
        $this->logger = $logger;
    }

    public function getBus() {
        return $this->bus;
    }

    public function setBus($bus) {
        $this->bus = $bus;
    }
}