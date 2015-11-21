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
 *
 * @package Hubbub
 *
 * The idea here is that with our get() method, we define a very standard way to get configuration data.  We don't use it as an object, or array, because
 *          doing so gives us a bit of a precondition, a -- specific way we must define and use the configuration system.  Using the simple/flexible dotted
 *          notation, we can have the backed configuration stored in any way we choose.
 *
 */
class Configuration {

    protected $array;

    public function __construct(\Hubbub\Logger $logger = null, \Hubbub\MessageBus $bus = null) {
        require 'conf/local-config.php';

        if(!empty($conf)) {
            $this->array = $conf;
        } else {
            throw new \Exception("Local configuration file does not contain a global \$conf variable!");
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
            } else {
                if($this->logger instanceof Logger) {
                    $this->logger->warning("Invalid configuration value: $value, couldn't find $piece inside $lastPiece");
                } else {
                    throw new \Exception("Can't find logger: the logger property instance not an instance of \\Hubbub\\Logger");
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