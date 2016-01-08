<?php
/*
 * This file is a part of Hubbub, available at:
 * http://github.com/abcarroll/hubbub
 *
 * Copyright (c) 2013-2015, A.B. Carroll <ben@hl9.net>
 * Hubbub is distributed under a BSD-like license.
 *
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code, or available at the URL above.
 */

namespace Hubbub\IRC;

/**
 * Class BncClient
 * @package Hubbub\IRC
 */
class BncClient {
    use Senders;

    /**
     * The client Id to identify this client against the server network object
     * @var int
     */
    public $clientId;

    /**
     * @var int
     */
    public $connectedSince;

    /**
     * @var string
     */
    protected $state = 'preauth';

    /**
     * @var int
     */
    protected $inStateSince;

    /**
     * @var string
     */
    public $nick;

    /**
     * @var string
     */
    public $user;

    /**
     * @var string
     */
    public $realName;

    /**
     * How many times have we tried to send PASS to login?
     * @var int
     */
    public $passwordAttempts = 0;

    /**
     * @var \Hubbub\Net\Server
     */
    public $net;


    public function __construct(\Hubbub\Net\Server $net, \Psr\Log\LoggerInterface $logger, $clientId) {

        $this->net = $net;
        $this->clientId = $clientId;
        $this->inStateSince = time();
        $this->logger = $logger;

        $this->logger->alert("I WAS PASSED CLIENTID: $clientId");

    }

    public function send($data) {
        $this->net->clientSend($this->clientId, $data . "\n");
    }

    public function disconnect() {
        $this->net->clientDisconnect($this->clientId);
    }

    public function setState($state) {
        $this->inStateSince = time();
        $this->state = $state;
    }

    public function getState() {
        return $this->state;
    }

    public function getSecondsInState() {
        return time() - $this->inStateSince;
    }

    public function getInStateSince() {
        return $this->inStateSince;
    }

    public function sendServerNotice($notice) {
        $this->send(":Hubbub.localnet NOTICE * :$notice");
    }
}