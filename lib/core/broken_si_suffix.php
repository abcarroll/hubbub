<?php
/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2013, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */


function si_number($number) {
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

echo "\n";

echo si_number(2000) . 'unit';

echo "\n\n";

die;

/*
    possibly break out into:

    iec_number() for > 1, base 1024
    si_number() for any number, base 1000

    how to have preferences?

    i.e. if we want to display ONLY full sec or milli?  or only full, milli, or micro ?


    Also, import the duration function


*/
