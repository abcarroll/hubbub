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

namespace Hubbub\Net;

abstract class Client implements \Hubbub\Iterable {

    /** @var  \Hubbub\Hubbub */
    public $hubbub;

    /** @var  \Hubbub\Net\Generic\Client */
    protected $net;

    public function __construct(\Hubbub\Hubbub $hubbub) {
        $this->hubbub = $hubbub;
        $class = $hubbub->conf->get('net.client');
        $this->net = new $class($this);
    }

    abstract public function on_connect();

    abstract public function on_disconnect();

    abstract public function on_send($data);

    abstract public function on_recv($data);
}