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
    public $conf, $logger, $iterator;

    function __construct(\Hubbub\Configuration $conf, \Hubbub\Logger $logger, \Hubbub\RootIterator $iterator) {
        $this->conf = $conf;
        $this->logger = $logger;
        $this->iterator = $iterator;

        /* Testing only , this would normally come from the conf */
        //$irc = new \Hubbub\IRC\Client($this);
        //$this->iterator->add($irc, 'irc-Freenode');

        $exampleProtocol = new \Hubbub\ExProto\Client($this);;
        $this->iterator->add($exampleProtocol, 'exampleProtocol');


        $this->run();
    }

    private function run() {
        $this->iterator->run();
    }
}