<?php
/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2015, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */

namespace Hubbub;

    /*
     * Warning: Logger is not available at this point! (FIXME)
     *
     * I am not impressed with this jumble of code.
     */

/**
 * Class Configuration
 *
 * @package Hubbub
 */
class Configuration {
    public $data;

    /**
     * @param \Hubbub\Hubbub $hubbub
     */
    public function __construct($hubbub) {
        include 'local-config.php';
        if(!empty($config)) {
            $this->data = $config;
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

    public function get() {

    }
}