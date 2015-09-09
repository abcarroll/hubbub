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
    /** @var  \Hubbub\Configuration */
    public $conf;
    /** @var  \Hubbub\Logger */
    public $logger;
    /** @var  \Hubbub\MessageBus */
    public $bus;
    /** @var  \Hubbub\Throttler\Throttler */
    public $throttler;
    /** @var  \Hubbub\Iterator */
    public $iterator;

    public function __construct(Array $dependants) {
        // Injects the $dependants array into this instance, which would be gathered from \Hubbub\Bootstrap in normal circumstance.
        array_walk($dependants, function($dVal, $dKey) {
            $this->$dKey = $dVal;
        });

        $this->init();
        $this->run();
    }

    public function init() {
        foreach($this->conf->get('hubbub') as $alias => $init) {
            $new = new $init['class']($this, $init['conf']);
            $this->iterator->add($new, $alias);
        }
    }

    public function run() {
        $this->iterator->run();
    }
}