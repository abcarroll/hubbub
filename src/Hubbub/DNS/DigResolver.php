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

namespace Hubbub\DNS;

/**
 * Class ShellResolver
 * @package Hubbub\DNS
 */
class DigResolver implements \Hubbub\Protocol\Client, \Hubbub\Iterable {
    /**
     * @var \Hubbub\Logger
     */
    protected $logger;

    /**
     * @var \Hubbub\MessageBus
     */
    protected $bus;

    /**
     * @var \Hubbub\ProcessManager
     */
    protected $processManager;

    protected $idHostTable = [];

    public function __construct(\Hubbub\ProcessManager $processManager, \Hubbub\Logger $logger, \Hubbub\MessageBus $bus, $name) {
        $this->processManager = $processManager;
        $this->logger = $logger;
        $this->bus = $bus;

        $this->bus->subscribe([$this, 'handleBusMessage']);
    }

    public function handleBusMessage($msg) {
        if(isset($msg['action']) && $msg['action'] == 'resolve') {
            $id = $this->processManager->execute('dig +short ' . escapeshellarg($msg['host']));
            $this->idHostTable[$id] = $msg['host'];
        }
    }

    public function iterate() {
        $completed = $this->processManager->pollCompleted();

        if(count($completed) > 0) {
            foreach($completed as $id => $result) {
                $host = $this->idHostTable[$id];
                // TODO: the right thing, this is a quick fix
                $result2 = '';
                $p = explode("\n", $result);
                foreach($p as $pp) {
                    if(filter_var($pp, FILTER_VALIDATE_IP)) {
                        $result2 = trim($pp);
                        break;
                    }
                }

                $this->bus->publish([
                    'protocol' => 'dns',
                    'action'   => 'resolve-complete',
                    'host'     => $host,
                    'result'   => $result2,
                ]);
            }
        }
    }

    /*
     * This brings up an interesting issue with our architecture:
     * Modules (iterable objects) aren't necessarily network objects.  We did good here by not needing to pass a network object by simply not including it
     * in our constructor, but otherwise we've failed miserably by needing to define blank methods here to satisfy the \Hubbub\Protocol\Client interface.
     */
    public function on_recv($data) {
        // TODO: Implement on_recv() method.
    }

    public function on_disconnect() {
        // TODO: Implement on_disconnect() method.
    }

    public function on_send($data) {
        // TODO: Implement on_send() method.
    }

    public function on_connect() {
        // TODO: Implement on_connect() method.
    }


}