<?php
/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2015, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */

namespace Hubbub;

class InternalBus {

    private $subscriptions;

    /**
     * Adds a subscription to the internal bus.  The callback will be notified whenever the
     * filter's critera is met.  The filter should be an array with zero or more of the following
     * named indexes:  originate, protocol, network, target, contents
     *
     * Note that while contents is probably all strings, you probably should not pass a
     *
     * @param array    $filter   An array-noted filter
     * @param callable $callback A callable function when the filter's critera is met
     */
    public function subscribe($filter, $callback) {
        $this->subscriptions[] = [
            'filter' => $filter,
            'callback' => $callback
        ];
    }

    function publish(array $event) {

        foreach($this->subscriptions as $sub) {

            foreach($sub['filter'] as $matchKey => $matchValue) {

                //if(isset())

            }

        }

        foreach ($this->subscriptions as $sObj => $sFilter) {
            foreach ($sFilter as $fKey => $fVal) {
                if($event[$fKey] != $fVal) {
                    break 2; // Some field didn't match, break from that subscription
                }
            }
            $this->objects[$sObj]->notify($event);
        }
        return true;
    }

}