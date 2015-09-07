<?php

/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2015, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */
namespace Hubbub;

/**
 * Class MessageBus
 *
 * @package Hubbub
 */
class MessageBus implements Iterable { // @todo not actually an iterable module ?

    protected $subscriptions;

    /**
     * @param callable          $callback
     * @param string|array|null $filter
     *
     * @return int The subscription key that can be used later to unsubscribe
     */
    public function subscribe($callback, $filter = null) {
        $this->subscriptions[] = [
            'filter'   => $filter,
            'callback' => $callback,
        ];

        return key(end($this->subscriptions));
    }


    public function unsubscribe($id) {
        if(isset($this->subscriptions[$id])) {

        }
    }

    public function publish($message) {

    }

    public function setBus(\Hubbub\MicroBus $bus) {
        // @todo
    }

    public function setConf(\Hubbub\Configuration $conf) {
        // @todo
    }

    public function setLogger(\Hubbub\Logger $logger) {
        // @todo
    }

    public function iterate() {
        // does nothing @todo
    }
}
