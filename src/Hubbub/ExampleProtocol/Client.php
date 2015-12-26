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

namespace Hubbub\ExampleProtocol;

/**
 * Class Client
 * @package Hubbub\ExampleProtocol
 */
class Client implements \Hubbub\Protocol\Client, \Hubbub\Iterable {
    /*
     * This is an example protocol that shows how the system works:
     *  - You extend \Hubbub\Protocol\Client, which is itself an extension of Iterable
     *  - Ask for any objects you want in the constructor: Logger, Conf, Bus, etc.
     *  - You'll want a \Hubbub\Net\Client object passed-in to handle networking ($net)
     *  - Now, just fill in your event handlers (on_connect, on_disconnect, etc)
     */

    /**
     * @var \Hubbub\Net\Client
     */
    protected $net;

    /**
     * @var \Hubbub\Logger
     */
    protected $logger;

    public function __construct(\Hubbub\Net\Client $net, \Hubbub\Logger $logger, $name) {
        $this->net = $net;
        $this->logger = $logger;
        $this->net->connect('127.0.0.1:80');

        $this->logger->info("Created ExampleProtocol client named: $name");
    }

    /**
     * Must implement these methods, they are abstracted in \Hubbub\Protocol\Client:
     */
    public function iterate() {
    }

    public function on_connect() {
        $this->logger->debug("Connected to my designated server");
    }

    public function on_disconnect() {
        $this->logger->debug("Disconnected from my designated server");
    }

    public function on_send($data) {
        $this->logger->debug("Sent some data to my designated server");
    }

    public function on_recv($data) {
        $this->logger->debug("Received some data from my designated server");
    }
}