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

namespace Hubbub;

/**
 * Class Utility
 *
 * @package Hubbub
 */
class Utility {
    /**
     * Converts an integer into a string using the best SI prefix.  For example 0.003 converts to '3 milli'.
     *
     * @param int $number The number to add a si prefix to.
     *
     * @return string A string with the original number passed + prefix, for instance "1234 giga"
     *
     * @todo Should we have an IECPrefix for base 2?
     * @todo Possibly add $asArray, or also "force" as a specific prefix, e.g. always show milli
     * @todo Abbreviation mode -- m instead of milli (so you can say <?=siPrefix(0.001)?>s for 1ms)
     * @todo Import duration function that complements this
     */

    const SI_SUFFIX_TIME = [[''], ['', 'ms', 'us']];
    const SI_SUFFIX_BYTES = [['KiB', 'MiB', 'GiB', 'TiB'], ['']];
    const SI_SUFFIX_STD = [['kilo', 'mega', 'giga', 'tera'], ['', 'milli', 'micro', 'nano', 'pico', 'femto', 'atto', 'zepto', 'yocto']];

    static public function siSuffix($number, $suffixSet = self::SI_SUFFIX_STD) {
        $suffix_gt1 = $suffixSet[0];
        $suffix_lt1 = $suffixSet[1];
        if($number >= 1) {
            for($i = 0; $number >= 1000 && $i < (count($suffix_gt1) - 1); $number /= 1000, $i++) {
                ;
            }

            return (round($number, 2) . ' ' . $suffix_gt1[$i]);
        } else {
            $i = 0;
            while($number < 1 || empty($suffix_lt1[$i])) {
                $number *= 1000;
                $i++;
            }

            return $number . ' ' . $suffix_lt1[$i];
        }
    }


    /**
     * Converts a string represented port range (6667-7000) into an array covering the ports in the range.
     *
     * @param string|int|array $str    A port or port range, such as 6667-7000
     * @param bool             $strict With strict mode true, anything not within the valid TCP/IP range of 0-65535 is rejected.
     *
     * @return array A list of individual ports covered in the range
     */
    static public function portRange($str, $strict = true) {
        $return = array();
        // remove any characters except digits ',' and '-'
        $str = preg_replace('/[^\d,-]/', '', $str);
        // split by ,
        $ports = explode(',', $str);
        if(!is_array($ports)) {
            $ports = array($ports);
        }
        foreach($ports as $p) {
            $p = explode('-', $p);
            if(count($p) > 1) {
                for($x = $p[0]; $x <= $p[1]; $x++) {
                    $return[] = $x;
                }
            } else {
                $return[] = $p[0];
            }
        }

        return $return;
    }

    /**
     * Converts either a array of integers or string of comma-separated integers to a natural english range, such as "1,2,3,5" to "1-3, 5".  It also supports
     * floating point numbers, however with some perhaps unexpected / undefined behaviour if used within a range.
     *
     * @param string|array $items    Either an array (in any order, see $sort) or a comma-separated list of individual numbers.
     * @param string       $itemSep  The string that separates sequential range groups.  Defaults to ', '.
     * @param string       $rangeSep The string that separates ranges.  Defaults to '-'.  A plausible example otherwise would be ' to '.
     * @param bool|true    $sort     Sort the array prior to iterating?  You'll likely always want to sort, but if not, you can set this to false.
     *
     * @return string
     */
    static function rangeToStr($items, $itemSep = ', ', $rangeSep = '-', $sort = true) {
        if(!is_array($items)) {
            $items = explode(',', $items);
        }
        if($sort) {
            sort($items);
        }
        $point = null;
        $range = false;
        $str = '';
        foreach($items as $i) {
            if($point === null) {
                $str .= $i;
            } elseif(($point + 1) == $i) {
                $range = true;
            } else {
                if($range) {
                    $str .= $rangeSep . $point;
                    $range = false;
                }
                $str .= $itemSep . $i;
            }
            $point = $i;
        }
        if($range) {
            $str .= $rangeSep . $point;
        }

        return $str;
    }

    /**
     * @return string The directory / git repository root where Hubbub is installed
     */
    static function baseDir() {
        return realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..');
    }
}