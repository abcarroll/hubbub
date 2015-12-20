<?php
/*
 * This file is a part of Hubbub, available at:
 * http://github.com/abcarroll/hubbub
 *
 * Copyright (c) 2015, A.B. Carroll <ben@hl9.net>
 * Hubbub is distributed under a BSD-like license.
 *
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code, or available at the URL above.
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