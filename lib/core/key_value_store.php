<?php
/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2013, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */

/*
    A very simple key value store

    The general API should follow redis.
    It's highly unlikely you would want to write an adapter
    for (p)redis except for edge cases, however if that edge
    case arises, we want to be ready.

    Additionally, it allows us to become familer with the redis
    API as we program without knowing it.

    It is highly unlikely we support 1/10 of the features of redis.
*/

class key_value_store {

    private $values = [];
    private $changes_since_save = 0;
    private $mtime = 0;

    private function record_change() {
        $this->changes_since_save++;
        $this->mtime = time();
    }


    public function set($key, $value, $ex_sec = 0, $px_msec = 0, $nx_xx = '') {
        if($ex_sec != 0 && $px_msec == 0) {
            $msec_expire = $ex_sec * 1000;
        } else {
            $msec_expire = $px_msec;
        }

        $nx_xx = strtolower($nx_xx);
        if($nx_xx == 'nx' && isset($this->values[$key])) {
            return false;
        } elseif($nx_xx = 'xx' && !isset($this->values[$key])) {
            return false;
        }

        $this->record_change();
        $this->values[$key] = [
            'value'  => $value,
            'expire' => $msec_expire,
            'time'   => microtime(1),
        ];

        return true;
    }

    public function get($key) {
        if(isset($this->values[$key])) {
            return $this->values[$key];
        } else {
            return false;
        }
    }

    public function incrby($key, $incr) {
        $this->record_change();
        if(!isset($this->values[$key])) {
            $this->values[$key] = [
                'value' => 0,
                'time'  => microtime(1)
            ];
        } elseif(!is_numeric($this->values[$key]['value'])) {
            throw Exception("Trying to INCRBY on the wrong data type");

            return;
        }

        $this->values[$key]['value']++;
    }

    public function incr($key) {
        return $this->incrby($key, 1);
    }

    public function bgsave() {
        return json_encode($this->values);
    }
}