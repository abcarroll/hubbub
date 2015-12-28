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
 * Class MessageBus is a class representing a pub/sub mechanism for passing messages between modules in a protocol agnostic way.
 *
 * It is a simple messaging gateway.  It allows for modules to broadcast their various events (creation, dns lookups, group chat subscriptions, chat messages,
 * among other things) as well as subscribe to other module's events to receive updates from them.  Messages across this bus are processed immediately, a
 * publish to the message bus will immediately be received by subscribed modules.  Subscribers have no obligation to respond.
 *
 * There is currently no formal definition of what a message looks like, however it is currently agreed that they are an associative array, perhaps containing
 * keys such as: protocol, network, action.
 *
 * @package Hubbub
 */
class MessageBus {
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * List of all subscriptions, key is 'id' and each item a ['filter' => [], 'callback' => callable...]
     * @var array
     */
    protected $subscriptions = [];

    /**
     * MessageBus constructor.
     *
     * @param Logger $logger
     *
     * @todo Remove logger if it's not going to be used here.
     */
    public function __construct(\Hubbub\Logger $logger) {
        $this->logger = $logger;
    }

    /**
     * Subscribes a callback to the message bus with the specified filters.
     *
     * @param callable   $callback
     * @param array|null $filter
     *
     * @return int A subscription key that can be used later to unsubscribe.
     *
     * @todo Implement a way pass a filter via string, i.e. 'protocol=dns/action=resolve-complete' or 'dns/resolve-complete'
     */
    public function subscribe($callback, $filter = null) {
        $this->subscriptions[] = [
            'callback' => $callback,
            'filter'   => $filter,
        ];

        return key(end($this->subscriptions));
    }


    /**
     * Removes a subscription/callback from the message bus.
     *
     * @param int $id The subscription key that was given at the time of subscription.
     */
    public function unsubscribe($id) {
        if(isset($this->subscriptions[$id])) {

        }
    }

    /**
     * Publish a message to the appropriately subscribed callbacks.
     *
     * @param array $message An associative array containing the message to pass to subscribed callbacks.
     */
    public function publish($message) {
        //$this->logger->debug("I have " . count($this->subscriptions) . " subscriptions I'm about to publish a received message to...");
        foreach($this->subscriptions as $s) {
            // call the callable
            $s['callback']($message);
        }
    }
}
