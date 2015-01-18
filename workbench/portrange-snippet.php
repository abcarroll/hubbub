<?php
/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2013, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */

// This is unintegrated.

// TODO handle backwards port ranges (e.g. passing an array and getting 6667, 6668, 7000-8000)
function port_range($str) {
    $return = array();
    // remove any characters except digits ',' and '-'
    $str = preg_replace('/[^\d,-]/', '', $str);
    // split by ,
    $ports = explode(',', $str);
    if(!is_array($ports)) {
        $ports = array($ports);
    }
    foreach ($ports as $p) {
        $p = explode('-', $p);
        if(count($p) > 1) {
            for ($x = $p[0]; $x <= $p[1]; $x++) {
                $return[] = $x;
            }
        } else {
            $return[] = $p[0];
        }
    }

    return $return;
}

?>