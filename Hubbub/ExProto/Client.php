<?php

/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2015, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */

namespace Hubbub\ExProto;

use StdClass;

/**
 * Thoughts: Instead of extending \Hubbub\Net\Stream\Client perhaps it should use dependency injection instead so you could
 * actually use the other kinds of Networking if necessary?
 */

/**
 * Class Client
 *
 * @package Hubbub\Modules\IRC
 */
class Client implements \Hubbub\Iterable, \Hubbub\Net\ClientUser {

    private $hubbub;
    /**
     * @var \Hubbub\Net\Generic\Client
     */
    private $net;

    public function __construct(\Hubbub\Hubbub $hubbub) {
        $this->hubbub = $hubbub;

        // I would much rather prefer:
        // $clientClass = $this->hubbub->conf->get('net/clientClass');
        // or even:
        // $clientClass = $this->conf->get('/net/clientClass');
        // or even:
        // $clientClass = $this->hubbub->getConf('/net/clientClass');
        // Maybe hubbub should actually implement everything injected there?
        $clientClass = $this->hubbub->conf['net']['client'];
        $this->net = new $clientClass($this);

        $this->net->connect('127.0.0.1:80');
    }

    // Must implement:

    public function iterate() {

        echo " > iterate!\n";

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