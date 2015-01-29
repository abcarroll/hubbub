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
class Hubbub extends \StdClass { // TODO Not sure if StdClass is the way to do it here
    /**
     * @var array                  $modules
     * @var \Hubbub\Throttler\Base $throttler
     * @var \Hubbub\Logger         $logger
     * @var \Hubbub\MicroBus       $bus
     */
    private $modules = [], $config, $throttler, $logger, $bus;

    /**
     * Initiates a new hubbub object.  Meant to be called once, to start an isolated instance.
     */
    public function __construct() {
        // Nothing here..
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
     * @param $config
     *
     * @throws \Exception
     */
    public function addModules($config) {
        $this->config = $config;

        foreach($config as $mKey => $mVal) {
            if(!empty($mVal['object'])) {
                $object = new $mVal['object'](
                    $this,
                    $this->getConfig(),
                    $this->getLogger(),
                    $this->getBus(),
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
    public function getConfig() {
        return $this->config;
    }

    public function setConfig($cfg) {
        $this->config = $config;
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