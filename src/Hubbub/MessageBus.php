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

namespace Hubbub;

/**
 * Class MessageBus
 *
 * @package Hubbub
 */
class MessageBus {
    protected $logger;
    protected $subscriptions = [];

    public function __construct(\Hubbub\Logger $logger) {
        $this->logger = $logger;
    }

    /**
     * @param callable          $callback
     * @param string|array|null $filter
     *
     * @return int The subscription key that can be used later to unsubscribe
     */
    public function subscribe($callback, $filter = null) {
        $this->subscriptions[] = [
            'callback' => $callback,
            'filter'   => $filter,
        ];

        return key(end($this->subscriptions));
    }


    public function unsubscribe($id) {
        if(isset($this->subscriptions[$id])) {

        }
    }

    public function publish($message) {
        //$this->logger->debug("I have " . count($this->subscriptions) . " subscriptions I'm about to publish a received message to...");
        foreach($this->subscriptions as $s) {
            $s['callback']($message); // call the callable
        }
    }

    public function iterate() {
        // does nothing @todo
    }
}
