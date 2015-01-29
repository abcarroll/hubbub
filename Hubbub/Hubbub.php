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
    public $config, $throttler, $bus;

    /**
     * Initiates a new hubbub object.  Meant to be called once, to start an isolated instance.
     */
    public function __construct() {
        // Nothing here..
    }


    /**
     * The main loop. This iterates over all the root modules and runs the injected throttler.
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

    public function addModules($config) {
        $this->config = $config;

        foreach($config as $mKey => $mVal) {
            if(!empty($mVal['object'])) {
                $object = new $mVal['object']($this, $mVal);

                if(!is_numeric($mKey)) {
                    $this->$mKey = $object;
                } else {
                    $this->modules[$mKey] = $object;
                }
            } else {
                throw new \Exception("addModule() called with a missing 'object' \$config index");
            }
        }
    }
}