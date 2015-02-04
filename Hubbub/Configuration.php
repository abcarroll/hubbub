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
class Configuration {
    /*
     * What a mess!
     */

    protected $logger;
    public $data;

    /**
     * @param \Hubbub\Hubbub $hubbub
     */
    public function __construct($hubbub) {
        require 'conf/bootstrap.php';
        if(!empty($conf)) {
            $this->data = $conf;
        } else {
            die("Configuration not properly defined!"); // TODO not very nice
        }
    }

    /**
     * @return object
     */
    public function getData() {
        return $this->data;
    }

    public function setLogger(\Hubbub\Logger $logger) {
        $this->logger = $logger;
    }
}