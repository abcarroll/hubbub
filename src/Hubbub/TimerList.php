<?php
/*
 * This file is a part of Hubbub, available at:
 * http://github.com/abcarroll/hubbub
 *
 * Copyright (c) 2016, A.B. Carroll <ben@hl9.net>
 * Hubbub is distributed under a BSD-like license.
 *
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code, or available at the URL above.
 */

namespace Hubbub;

class TimerList {
    protected $timerList = array();
    protected $timerIdCounter = 0;

    /**
     * Checks the timers and execute callbacks if they're ready
     */
    public function checkTimers() {
        foreach($this->timerList as $name => $timer) {
            if($timer['time'] <= time()) {
                $timer['call']();
                unset($this->timerList[$name]);
            }
        }
    }

    /**
     * Add a timer to be executed in N seconds.
     *
     * @param callable    $call  The callback to execute when the timer is ready
     * @param int         $inSec How many seconds in the future to run the callback
     * @param string|null $name  An optional name to later reference the timer by.  If you do not specify a name, one will be provided for you.
     *
     * @return string The name of the timer.  If you didn't specify one, the self-generated name will be supplied.
     */
    public function addBySeconds($call, $inSec, $name = null) {
        return $this->addByTime($call, (time() + $inSec), $name);
    }

    /**
     * Add a timer to the list, giving an exact point in the future to execute the timer.
     *
     * @param callable    $call The callback to execute when the timer is ready
     * @param int         $time When to execute the timer as a unix timestamp
     * @param string|null $name An optional name to later reference the timer by.  If you do not specify a name, one will be provided for you.
     *
     * @return string The name of the timer.  If you didn't specify one, the self-generated name will be supplied.
     *
     * @todo Accept DateTime objects for $time
     * @todo Allow for multiple executions, eg. an $executeThisManyTimes parameter
     */
    public function addByTime($call, $time, $name = null) {
        $timerId = $this->timerIdCounter++;

        if($name === null) {
            $name = 'timer-' . $timerId;
        }

        $this->timerList[$name] = [
            'id'   => $timerId,
            'name' => $name,
            'time' => $time,
            'call' => $call,
        ];

        return $name;
    }

    /**
     * Extend a timer's execution time by $seconds seconds.
     *
     * @param string $name    The timer name to extend
     * @param int    $seconds Further delay execution by this many seconds
     */
    public function extendBySeconds($name, $seconds) {
        if(isset($this->timerList[$name])) {
            $this->timerList[$name]->time += $seconds;
        }
    }

    /**
     * Extend a timer's execution time until the new $time, as a Unix timestamp.  In other words, completely overwrite the timer's execution time.
     *
     * @param string $name The name of the timer to change
     * @param int    $time The unix timestamp to extend it to
     */
    public function extendToTime($name, $time) {
        if(isset($this->timerList[$name])) {
            $this->timerList[$name]->time = $time;
        }
    }

    /**
     * Remove a timer completely.
     *
     * @param string $name The timer to remove
     */
    public function remove($name) {
        unset($this->timerList[$name]);
    }

}