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
 * Class Configuration
 *
 * @package Hubbub
 */
class Configuration extends \ArrayObject {
    protected $conf, $logger, $bus;

    public function __construct(\Hubbub\Logger $logger = null, \Hubbub\MessageBus $bus = null) {
        require 'conf/local-config.php';
        if(!empty($conf)) {
            $this->exchangeArray($conf);
        } else {
            throw new \Exception("Local configuration file does not contain a global \$conf variable!");
        }
    }
}