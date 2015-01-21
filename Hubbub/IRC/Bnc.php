<?php

/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2013, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */


namespace Hubbub\IRC;

/**
 * Thoughts: This might should just be called IRC server.  Or implement a basic server and then extend that out to a bnc...
 * Also, same with IRC server, it should probably use dependency injection for the Net Stream Server instead of extending it.
 *
 * @todo Move clients table and on_client_recv to a $client->on_recv() .. Should likely do both
 */

/**
 * Class Bnc
 */
class Bnc extends \Hubbub\Net\Stream\Server {
    protected $hubbub;
    protected $clients;
    protected $config;

    public function __construct(\Hubbub\Hubbub $hubub, Array $config) {
        $this->hubbub = $hubub;
        $this->config = $config;
        parent::__construct($this->config['listen']);
    }

    function on_client_connect($socket) {
        $newClient = new BncClient($this->hubbub, $this, $socket);
        $newClient->iterate(); // Iterate once after connection automatically
        $this->clients[(int) $socket] = $newClient;
    }

    function on_client_disconnect($socket) {
        unset($this->clients[(int) $socket]);
    }

    function on_client_recv($socket, $data) {
        /** @var BncClient $client */
        $client = $this->clients[(int) $socket];
        $client->on_recv($data);
    }

    function on_client_send($socket, $data) {
        /* this may be a moot method anyway.  we have no way of actually controlling
           when data is sent. */
    }

    // I'm not sure if it makes sense to iterate clients
    function on_iterate() {
        if(count($this->clients) > 0) {
            foreach ($this->clients as $c) {
                /** @var $c BncClient */
                $c->iterate();
            }
        }
        $this->hubbub->logger->debug("BNC Server was iterated with " . count($this->clients) . " clients");
    }
}
