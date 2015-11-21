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
    static function SiPrefix($number) {
        $suffix_gt1 = ['kilo', 'mega', 'giga', 'tera'];
        $suffix_lt1 = ['', 'milli', 'micro', 'nano', 'pico', 'femto', 'atto', 'zepto', 'yocto'];
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
     * @param string|int|array $str A port range, such as 6667-7000
     *
     * @return array A list of individual ports covered in the range
     *
     * @todo Handle backwards port ranges (e.g. passing an array and getting 6667, 6668, 7000-8000)
     */
    function portRange($str) {
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

}