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
 * Class Bootstrap
 *
 * @package Hubbub
 */
class Bootstrap {

    /**
     * Returns the CLI argument-specified or default bootstrap configuration file's array.
     *
     * @return array
     */
    static public function getDependenciesArray() {

        if(empty($argv[1])) {
            $bootstrapFile = 'conf/bootstrap.php';
        } else {
            $bootstrapFile = $argv[1];
        }

        return include($bootstrapFile);
    }

    /**
     * @return array An associative array containing the
     * @throws \Exception
     */
    static public function loadDependencies(Array $bootstrap) {
        // This injects everything with everything.
        // It's a bit over-engineered, I've been told.

        if(!empty($bootstrap)) {

            $dependencies = [];
            $dependencyQueue = [];

            // Initialize
            foreach($bootstrap as $depName => $depCfg) {

                // echo " > Bootstrapping $depName as {$depCfg['class']}\n";
                $dependencies[ $depName ] = new $depCfg['class']();

                // Each module that this dependency requests
                if(!empty($depCfg['inject'])) {
                    foreach($depCfg['inject'] as $injName) {
                        if(isset($dependencies[ $injName ])) {
                            // The requested dependency is already initiated
                            // echo " >  Injecting $injName into $depName\n";
                            $dependencies[ $depName ]->{'set' . $injName}($dependencies[ $injName ]);
                        } else {
                            // Else, add it to the queue for when it is initiated
                            // echo " >  Queueing $injName to be injected into $depName\n";
                            $dependencyQueue [ $injName ][] = $depName;
                        }
                    }
                }

                // Each module that has requested this dependency before it was initiated ($dependencyQueue)
                if(!empty($dependencyQueue[$depName]) && count($dependencyQueue[$depName]) > 0) {
                    foreach($dependencyQueue[$depName] as $injectIn) {
                        // echo " >  Late-Injecting $depName into $injectIn\n";
                        $dependencies [$injectIn]->{'set' . $depName}($dependencies[$depName]);
                    }
                }

            }

        } else {
            throw new \Exception("Missing bootstrap file or it doesn't provide a \$bootstrap array");
        }

        return $dependencies;
    }

    /**
     * Sets up the environment in early stages of execution.
     */
    static public function Sunrise() {
        echo "Hubbub session started " . date('r') . "\n";

        // All errors + strict
        error_reporting(E_ALL | E_STRICT);

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

}