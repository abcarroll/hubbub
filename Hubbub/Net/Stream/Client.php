<?php

/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2015, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */

namespace Hubbub\Net\Stream;

/**
 * Class Client
 *
 * @package Hubbub\Net\Stream
 */
class Client implements \Hubbub\Net\Generic\Client {

    private $parent;
    private $socket;
    private $connected = false;


    public function __construct(\Hubbub\Net\ClientUser $parent) {
        $this->parent = $parent;
    }

    public function connect($where) {
        die("Connecting to: " . $where);

        $this->socket = stream_socket_client($where, $errno, $errstr, STREAM_CLIENT_ASYNC_CONNECT);
    }

    public function disconnect() {
        throw new \ErrorException("disconnect() not implemented!");
    }

    public function set_blocking($block = true) {
        return stream_set_blocking($this->socket, $block);
    }

    public function send($data) {
        $this->parent->on_send($data);
        return fwrite($this->socket, $data);
    }

    public function recv($length = 4096) {
        $data = fread($this->socket, $length);
        $this->parent->on_recv($data);
    }

    public function iterate() {
        if(!$this->connected && !feof($this->socket)) {
            $this->connected = true;
            $this->parent->on_connect();
        } elseif(feof($this->socket)) {
            $this->parent->on_disconnect();
        } else {
            $this->recv();
        }
    }

    // This part is confusing .. Do we have these in \Hubbub\Net\Generic\Client anyway?  And just force them to call the "parent"?
    // I don't think so ..
    function on_connect() {
    }

    function on_disconnect() {
    }

    function on_send($data) {

    }

    function on_recv($data) {

    }
}
