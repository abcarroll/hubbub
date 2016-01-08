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
 * Class DigResolver
 * @package Hubbub\DNS
 */
class DigResolver implements \Hubbub\Protocol\Client, \Hubbub\Iterable {
    /**
     * @var \Hubbub\Logger
     */
    protected $logger;

    /**
     * @var \Hubbub\Configuration
     */
    protected $conf;

    /**
     * @var \Hubbub\MessageBus
     */
    protected $bus;

    /**
     * The process manager object
     * @var \Hubbub\ProcessManager
     */
    protected $processManager;

    /**
     * A table containing every "id" with the host it is resolving and when resolution began
     * @var array
     */
    protected $idTable = [];

    /**
     * Contains recently cached lookups
     * @var array
     */
    protected $resolverCache = [];

    /**
     * Last garbage collection as a unix timestamp
     * @var int
     */
    protected $lastGc = 0;

    /**
     * How long in between cache invalidation
     * @var int
     */
    protected $gcEverySec = 30;

    public function __construct(\Hubbub\ProcessManager $processManager, \Hubbub\Logger $logger, \Hubbub\Configuration $conf, \Hubbub\MessageBus $bus, $name) {
        $this->processManager = $processManager;
        $this->logger = $logger;
        $this->conf = $conf;
        $this->bus = $bus;

        $this->bus->subscribe([$this, 'handleBusMessage']);
    }

    public function handleResponse($msg, $cacheResponse = true) {
        $this->bus->publish($msg);
        if($cacheResponse && $msg['status'] == 'ok') {
            $msg['expires-at'] = time() + 3600;
        } else {
            // Only cache negative responses for 30 sec, this is more-or-less to prevent creating a flood of dig processes if something keeps requesting it
            $msg['expires-at'] = time() + 30;
        }

        $this->resolverCache[$msg['host']] = $msg;

    }


    /**
     * Handles DNS protocol bus messages
     *
     * @param $msg
     *
     * @throws \ErrorException
     *
     * @todo Implement bus filters and remove if() statements regarding protocol/action selection
     */
    public function handleBusMessage($msg) {
        if(!(isset($msg['protocol']) && $msg['protocol'] == 'dns')) {
            return $msg;
        }

        // Handle action=resolve
        if(isset($msg['action']) && $msg['action'] == 'resolve') {
            // Is it already cached?
            if(isset($this->resolverCache[$msg['host']]) && $this->resolverCache[$msg['host']]['expires-at'] < time()) {
                $this->handleResponse($this->resolverCache[$msg['host']], false);
                return $msg;
            }

            $timeout = isset($msg['timeout']) ? $msg['timeout'] : $this->conf->get('dns/default-timeout');
            $timeout = empty($timeout) ? 3 : $timeout;

            $tries = isset($msg['tries']) ? $msg['tries'] : $this->conf->get('dns/default-tries');
            $tries = empty($tries) ? 4 : $tries;

            $commandLine = escapeshellcmd($this->conf->get('dns/dig-path')) .
                           ' @' . $this->conf->get('dns/servers/0') .
                           ' ' . escapeshellarg($msg['host']) .
                           ' +tries=' . escapeshellarg($tries) .
                           ' +timeout=' . escapeshellarg($timeout) .
                           ' +short';

            try {
                $id = $this->processManager->execute($commandLine);

                $this->idTable[$id] = [
                    'host'       => $msg['host'],
                    'timeout-at' => time() + $timeout
                ];
            } catch(\ErrorException $e) {
                $this->logger->critical("Failed to start 'dig': " . $e->getMessage());

                $this->handleResponse([
                    'protocol' => 'dns',
                    'action'   => 'response',
                    'status'   => 'error',
                    'host'     => $msg['host'],
                    'message'  => 'Failed to start dig for DNS resolution',
                ]);
            }
        } elseif(isset($msg['action']) && $msg['action'] == 'flush-cache') {
            $this->resolveCache = [];
        }

        return $msg;
    }

    public function iterate() {
        $completed = $this->processManager->pollCompleted();

        // Handle completions
        if(count($completed) > 0) {
            foreach($completed as $id => $rawOutput) {
                $host = $this->idTable[$id]['host'];
                $outputLines = explode("\n", $rawOutput);

                $response = [];
                foreach($outputLines as $line) {
                    if(filter_var($line, FILTER_VALIDATE_IP)) {
                        $response[] = trim($line);
                        break;
                    }
                }

                $this->handleResponse([
                    'protocol' => 'dns',
                    'action'   => 'response',
                    'status'   => 'complete',
                    'host'     => $host,
                    'result'   => $response,
                ]);

                unset($this->idTable[$id]);
            }
        }

        // Garbage collection: timeouts

        foreach($this->idTable as $id => $m) {
            // This should never happen since dig should always gracefully timeout itself, which is why we give an additional 60 seconds grace period
            if(time() > ($m['timeout-at'] + 10)) {
                $this->handleResponse([
                    'protocol' => 'dns',
                    'action'   => 'response',
                    'status'   => 'timeout',
                    'host'     => $m['host'],
                    'message'  => 'The DNS "dig" never responded.'
                ]);
                unset($this->idTable[$id]);
            }
        }

        if(time() > ($this->lastGc + 30)) {
            // Garbage collection: cache
            foreach($this->resolverCache as $host => $c) {
                if(time() > $c['expires-at']) {
                    unset($this->resolverCache[$host]);
                }
            }
            $this->lastGc = time();
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