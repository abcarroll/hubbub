<?php

    /*
     * This file is a part of Hubbub, freely available at http://hubbub.sf.net
     *
     * Copyright (c) 2013, Armond B. Carroll <ben@hl9.net>
     * For full license terms, please view the LICENSE.txt file that was
     * distributed with this source code.
     */

    class microbus {
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
                $this->objects[$sObj]->notify($event);
            }
        }

        function subscribe(module $obj, array $filter = []) {
            $this->subscriptions[(string) $obj] = $filter;
            $this->objects[(string) $obj] = $obj;
        }

        function unsubscribe(module $obj, array $filter = []) {
            unset($this->subscriptions[$obj]);
        }
    }
