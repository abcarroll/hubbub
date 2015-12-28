<?php
/*
 * This file is a part of Hubbub, available at:
 * http://github.com/abcarroll/hubbub
 *
 * Copyright (c) 2015, A.B. Carroll <ben@hl9.net>
 * Hubbub is distributed under a BSD-like license.
 *
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code, or available at the URL above.
 */

namespace Hubbub;

/**
 * Class Configuration
 * The idea here is that with our get() method, we define a very standard way to get configuration data.  We don't use it as an object, or array, because
 *          doing so gives us a bit of a precondition, a -- specific way we must define and use the configuration system.  Using the simple/flexible dotted
 *          notation, we can have the backed configuration stored in any way we choose.
 *
 * @package Hubbub
 */
class Configuration {
    protected $array;

    public function __construct() {

        $globalConfig = [];
        $confFiles = glob("conf/*.conf.php");

        foreach($confFiles as $filename) {
            // TODO: Improve merge algorithm for configuration merging
            // We should probably use a better merge here so that specifically non-array conflicts are overwritten instead of forming a child array
            $globalConfig = array_merge_recursive($globalConfig, include($filename));
        }

        if(!empty($globalConfig)) {
            $this->array = $globalConfig;
        } else {
            throw new \Exception("No configuration values were loaded.  Check that a minimal config exists in conf/");
        }
    }

    /**
     * Get a configuration value.  The value should look something like "some.configuration.value".  The function can return any type: an array if a tree
     * is requested, or any other type if found.  It returns NULL to indicate failure to find the value, and issues a warning via the logger.
     *
     * @param $value string The configuration value to get
     *
     * @return mixed The configuration value requested, or null if not found.
     *
     * @throws \Exception
     */
    public function get($value) {
        // echo " >> (config) getting value: $value\n";
        $current = $this->array;
        $pieces = explode('.', $value);
        $lastPiece = null;
        foreach($pieces as $piece) {
            // echo " >> (config) Dropping down into $piece\n";
            if(isset($current[$piece])) {
                if(is_array($current[$piece]) && end($pieces) !== $piece) {
                    $current = $current[$piece];
                }
            }
            $lastPiece = $piece;
        }

        if(isset($current[$lastPiece])) {
            return $current[$lastPiece];
        } else {
            return null;
        }
    }
}