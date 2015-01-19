<?php
/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2015, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */

//namespace Hubbub\Utility;;

class Utility {

    static function CheckEnv() {
        /// Check if we're running a web instance
        if(php_sapi_name() != 'cli') {
            header('Content-Type: text/plain');
            ob_implicit_flush();
            echo "I think I am running in a web environment.  I normally need to be run in a shell.  I will continue anyway, but please be advised this might be a bad idea.\n";
        }
    }
}