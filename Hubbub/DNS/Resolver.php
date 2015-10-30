<?php
/*
 * This file is a part of Hubbub, available at:
 * http://github.com/abcarroll/hubbub
 *
 * Copyright (c) 2013-2015, A.B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */


namespace Hubbub\DNS;

/**
 * Class Resolver
 * @package Hubbub\DNS
 */
/*
 * Not sure if this is going to stick, but currently we have a serious global lock issue during DNS lookups..
 */
class Resolver {
    // Seconds that DNS entries will be cached.  0 to disable.  Normally disabling this means that an OS-level cache will be used instead.
    static protected $internalCacheTime = 0;
    static protected $dnsCache = [];

    static public function getAddrByHost($host) {
        if(self::$internalCacheTime > 0 && isset(self::$dnsCache[$host]) && self::$dnsCache[$host] <= time()) {
            return self::$internalCacheTime[$host];
        } else {
            $result = gethostbyname($host);
            if($host === $result) {
                return false;
            } else {
                self::$dnsCache[$host] = $result;
                return $result;
            }
        }
    }
}
