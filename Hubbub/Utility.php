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
 * Class Utility
 *
 * @package Hubbub
 */
class Utility {

    /**
     * Sets up the environment in early stages of execution.  This is a bootstrap-ish type piece of code.
     */
    static public function Sunrise() {
        echo "Hubbub session started " . date('r') . "\n";

        // See http://php.net/errorfunc.configuration.php#ini.error-reporting
        error_reporting(2147483647);

        // Check if we're running a web instance
        if(php_sapi_name() != 'cli') {
            header('Content-Type: text/plain');
            ob_implicit_flush();

            // TODO Should this be changed to a warning?
            echo "I think I am running in a web environment.  I normally need to be run in a shell.  I will continue anyway, but please be advised this might be a bad idea.\n";
        }
    }

    /**
     * Clean up the environment in a graceful shutdown situation
     */
    static public function Sunset() {
        echo date('r') . "\n";
        echo "Hubbub gracefully shut down.\n";
    }

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
            for ($i = 0; $number >= 1000 && $i < (count($suffix_gt1) - 1); $number /= 1000, $i++) ;

            return (round($number, 2) . ' ' . $suffix_gt1[$i]);
        } else {
            $i = 0;
            while ($number < 1 || empty($suffix_lt1[$i])) {
                $number *= 1000;
                $i++;
            }

            return $number . ' ' . $suffix_lt1[$i];
        }
    }

}