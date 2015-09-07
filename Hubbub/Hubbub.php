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
class Hubbub extends Injectable {
    public $iterator;

    function __construct(\Hubbub\Configuration $conf, \Hubbub\Logger $logger, \Hubbub\Iterator $iterator) {
        $this->conf = $conf;
        $this->logger = $logger;
        $this->iterator = $iterator;

        foreach($this->conf->get('hubbub') as $alias => $init) {
            $new = new $init['class']($this, $init['conf']);
            $this->iterator->add($new, $alias);
        }
    }

    public function run() {
        $this->iterator->run();
    }
}