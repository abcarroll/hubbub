<?php

/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2015, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */

namespace Hubbub\ExProto;

/**
 * Class Client
 * @package Hubbub\ExProto
 */
class Client extends \Hubbub\Net\Client {
    public function __construct(\Hubbub\Hubbub $hubbub) {
        parent::__construct($hubbub);
        $this->net->connect('127.0.0.1:80');
    }


    /**
     * Must implement these methods, they are abstracted in \Hubbub\Net\Client:
     */
    public function iterate() {
    }

    public function on_connect() {
    }

    public function on_disconnect() {
    }

    public function on_send($data) {
    }

    public function on_recv($data) {
    }
}