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


namespace Hubbub\IRC;

/**
 * Class Bnc
 * @package Hubbub\IRC
 */
class Bnc implements \Hubbub\Protocol\Server, \Hubbub\Iterable {
    /*
     * Thoughts: This might should be called \Hubbub\IRC\Server and then extend-out a BNC based on the generic IRC server implementation, in the future.
     * @todo: Move clients table and on_client_recv() to $client->on_recv().
     */
    //use Generic;

    protected $net, $conf, $logger, $bus;
    protected $clients = [];

    protected $channels = [];

    public function __construct(\Hubbub\Net\Server $net, \Hubbub\Configuration $config, \Hubbub\Logger $logger, \Hubbub\MessageBus $bus, $name) {
        $this->conf = $config;
        $this->logger = $logger;
        $this->bus = $bus;

        $this->net = $net;
        $this->net->setProtocol($this);

        $this->bus->subscribe([$this, 'busMessageHandler'], [
            'protocol' => 'irc'
        ]);

        $listen = $this->conf->get('irc.bnc.listen');
        $this->net->server('tcp://' . $listen);
    }

    /*public function clientDisconnect($socket) {
        stream_socket_shutdown($socket, STREAM_SHUT_RDWR);
    }*/

    public function busMessageHandler($message) {

        // group subscribe, group join, i_join, i_subscribe, ... etc
        /*if($message->action == 'group_subscribe') {

        }*/

        foreach($this->clients as $c) {
            /** @var \Hubbub\IRC\BncClient $c */
            $c->onBusMessage($message);
        }
    }

    function on_client_connect($socket) {
        $newClient = new BncClient($this, $this->logger, $this->conf, $this->bus, $socket);
        $newClient->iterate(); // Iterate once after connection automatically
        $this->clients[(int) $socket] = $newClient;
    }

    function on_client_disconnect($socket) {
        unset($this->clients[(int) $socket]);
    }

    function on_client_recv($socket, $data) {
        $this->logger->debug("Received data from client: $data");

        /** @var \Hubbub\IRC\BncClient $client */
        $client = $this->clients[(int) $socket];
        $client->on_recv($data);
    }

    function on_client_send($socket, $data) {
        /* this may be a moot method anyway.  we have no way of actually controlling
           when data is sent. */

        $this->logger->debug("BNC::on_client_send: " . $data);
    }

    /* Does this trigger??? */
    function on_recv($line) {
        $cmd = $this->parse_irc_cmd($line);
        print_r($cmd);
    }

    /*
     * From Message Bus
     */
    function __toString() {
        return 'bnc';
    }

    function on_notify($n) {
        print_r($n);
    }

    function on_listen() {
        // TODO: Implement on_listen() method.
    }

    function on_send($client, $data) {
        // TODO: Implement on_send() method.
    }

    function iterate() {
        $this->net->iterate();

        if(count($this->clients) > 0) {
            foreach($this->clients as $c) {
                /** @var $c BncClient */
                $c->iterate();
            }
        }
        //$this->logger->debug("BNC Server was iterated with " . count($this->clients) . " clients");
    }

    public function send($who, $what) {
        return $this->net->send($who, $what);
    }
}
