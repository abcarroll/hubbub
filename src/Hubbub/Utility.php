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
 * Utility class providing assorted functionality that doesn't fit elsewhere.
 *
 * @package Hubbub
 */
class Utility {
    const SI_SUFFIX_TIME = 1;
    const SI_SUFFIX_BYTES = 2;
    const SI_SUFFIX_STD = 4;

    /**
     * Converts an integer into a string using a SI suffix, e.g. 0.003 converts to '3 milli'.
     *
     * siSuffix will add the proper SI unit suffix for various units.  The default suffixSet, Utility::SI_SUFFIX_STD will add 'kilo', 'mega', 'giga', etc for
     * integers above 1, and for integers below 1, will add 'milli', 'micro', etc.  You may specify other suffix sets: SI_SUFFIX_STD, SI_SUFFIX_BYTES,
     * or SI_SUFFIX_TIME.
     *
     * @param       $number
     * @param array $suffixSet
     *
     * @return string
     *
     * @todo Should we have an IECPrefix for base 2?
     * @todo Possibly add $asArray, or also "force" as a specific prefix, e.g. always show milli
     * @todo Abbreviation mode -- m instead of milli (so you can say <?=siPrefix(0.001)?>s for 1ms)
     * @todo Import duration function that complements this
     */
    static public function siSuffix($number, $suffixSet = self::SI_SUFFIX_STD) {
        /*
         * This is done as such for hhvm compatibility
         */
        if($suffixSet == self::SI_SUFFIX_TIME) {
            $suffixSet = [[''], ['', 'ms', 'us']];
        } elseif($suffixSet == self::SI_SUFFIX_BYTES) {
            $suffixSet = [['KiB', 'MiB', 'GiB', 'TiB'], ['']];
        } elseif($suffixSet == self::SI_SUFFIX_STD) {
            $suffixSet = [['kilo', 'mega', 'giga', 'tera'], ['', 'milli', 'micro', 'nano', 'pico', 'femto', 'atto', 'zepto', 'yocto']];
        }

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
     * Converts a set of numbers to their shorthand ranges, for example "1,2,3,9,10" to "1-3,9-10".
     *
     * You may use either a array of integers or string of comma-separated integers to convert to a natural English range, such as "1,2,3,5" to "1-3, 5".
     * Floating point numbers will not be discarded, but may have unxpected behaviour.
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
     * Returns the base directory of the installation.
     *
     * @return string The directory / git repository root where Hubbub is installed
     */
    static function baseDir() {
        return realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..');
    }

    /**
     * Returns a simple one-line dump of the value passed, appropriate for passing into a line-by-line logger.
     *
     * Utility::varDump() is a recursive function which attempts to produce a one-lined dump that is or is nearly runnable PHP code.  If enabled, the second
     * parameter $showTypes will additionally show the variable data type as a cast before the value print.  It is not guaranteed that the output be valid PHP
     * code.  There are many situations where it will not be valid, such as passing resources, or newlines in the value.
     *
     * @param            $var
     * @param bool|false $showTypes
     *
     * @return string
     *
     * @todo Try to improve 'run-ability' for output.  Change escape sequence str_replace to automatically replace any/all non-alphanumeric sequences.
     */
    static function varDump($var, $showTypes = false) {
        $b = '';
        if(is_array($var)) {
            $b .= '[';
            foreach($var as $k => $v) {
                $b .= "'" . addslashes($k) . "' => " . self::varDump($v) . ", ";
            }
            $b = substr($b, 0, -2) . '], ';
        } elseif($var === null) {
            $b .= 'NULL, ';
        } elseif(is_string($var) || is_int($var) || is_float($var) || is_double($var)) {
            $b .= '(' . gettype($var) . ') ';
            $var = str_replace("\t", '\n', $var);
            $var = str_replace("\r", '\n', $var);
            $var = str_replace("\n", '\n', $var);
            $b .= "'" . addslashes($var) . "'";
        } else {
            $b .= '(' . gettype($var) . ')';
        }

        return $b;
    }

    /**
     * Returns a hexadecimal representation of either time() or microtime().  Do NOT use this for a unique ID.
     *
     * @param bool|false $microtime Whether or not to return the microtime portion in hex.
     *
     * @return string A unix timestamp (or timestamp+microtime) represeted as hexadecimal
     */
    static function hexTime($microtime = false) {
        if(!$microtime) {
            return $time = sprintf("%08x", time());
        } else {
            $m = explode(' ', microtime());
            $time = sprintf("%08x%05x", $m[1], ($m[0] * 1000000));
            return $time;
        }
    }
}