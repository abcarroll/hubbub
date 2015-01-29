<?php

/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2013, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */

namespace Hubbub\Net\Stream;

/**
 * Class Client
 *
 * @package Hubbub\Net\Stream
 */
abstract class Client implements \Hubbub\Net\Generic\Client {

    private $socket;
    protected $connected = false;

    function __construct() {
        //nothing
    }

    function connect($where) {
        $this->socket = stream_socket_client($where, $errno, $errstr, STREAM_CLIENT_ASYNC_CONNECT);
    }

    function set_blocking($block = true) {
        return stream_set_blocking($this->socket, $block);
    }

    function send($data) {
        return fwrite($this->socket, $data);
    }

    function iterate() {
        if(!$this->connected && !feof($this->socket)) {
            $this->connected = true;
            $this->on_connect();
        } elseif(feof($this->socket)) {
            $this->on_disconnect();
        } else {
            $data = fread($this->socket, 4096);
            $this->on_recv($data);
        }
    }
}
