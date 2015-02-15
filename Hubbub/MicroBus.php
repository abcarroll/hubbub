<?php

/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2013, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */

namespace Hubbub;

    /**
     * MicroBus is a proof of concept messaging bus that closely resembles Redis's PUB/SUB mechnism.
     *
     * @todo Implement a full message bus.
     */

/**
 * Class MicroBus
 */
class MicroBus implements MessageBus, IterableModule {
    private $subscriptions = [];
    private $objects = [];

    function notify(array $event) {
        return $this->publish($event);
    }

    function publish(array $event) {
        foreach ($this->subscriptions as $sObj => $sFilter) {
            foreach ($sFilter as $fKey => $fVal) {
                if($event[$fKey] != $fVal) {
                    break 2; // Some field didn't match, break from that subscription
                }
            }
            $this->objects[$sObj]->on_notify($event);
        }
        return true;
    }

    function subscribe($obj, array $filter = []) {
        $this->subscriptions[(string) $obj] = $filter;
        $this->objects[(string) $obj] = $obj;
    }

    function unsubscribe($obj, array $filter = []) {
        unset($this->subscriptions[$obj]);
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
        // nothing yet
    }
}
