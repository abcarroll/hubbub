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
 * Class Bootstrap
 *
 * @package Hubbub
 */
class Bootstrapper {
    /**
     * Whether or not to directly 'echo' debugging information.  This is so close to the start procedures, logging facilities are not yet created.
     * @const bool VERBOSE
     */
    const VERBOSE = true;

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
     * @return array An associative array containing the loaded & injected dependencies
     * @throws \Exception
     * @deprecated This has been superseded by Dice.  Remove when ready.
     */
    static public function loadDependencies(Array $bootstrap) {
        // This injects everything with everything.
        // It's a bit over-engineered, I've been told.

        if(!empty($bootstrap)) {

            $dependencies = [];
            $dependencyQueue = [];

            // Initialize
            foreach($bootstrap as $depName => $depCfg) {

                if(self::VERBOSE) {
                    echo " > Creating object '$depName' as '{$depCfg['class']}'\n";
                }

                if(isset($bootstrap['constructorInject']) && $bootstrap['constructorInject'] === true) {
                    $dependencies[$depName] = new $depCfg['class']($dependencies);
                } else {
                    $dependencies[$depName] = new $depCfg['class']();
                }

                // Each module that this dependency requests
                if(!empty($depCfg['inject'])) {
                    foreach($depCfg['inject'] as $injName) {
                        if(isset($dependencies[$injName])) {
                            // The requested dependency is already initiated
                            if(method_exists($dependencies[$depName], 'set' . $injName)) {
                                if(self::VERBOSE) {
                                    echo " >> Injecting '$injName' into '$depName' via setX() method\n";
                                }
                                $dependencies[$depName]->{'set' . $injName}($dependencies[$injName]);
                            } elseif(method_exists($dependencies[$depName], 'inject')) {
                                if(self::VERBOSE) {
                                    echo " >> Injecting '$injName' into '$depName' via Injectable inject() method\n";
                                }
                                $dependencies[$depName]->inject($injName, $dependencies[$injName]);
                            } else {
                                throw new \ErrorException("No way to inject $injName into $depName via standard Hubbub inject() method.");
                            }
                        } else {
                            // Else, add it to the queue for when it is initiated
                            if(self::VERBOSE) {
                                echo " >> Queueing $injName to be injected into $depName\n";
                            }
                            $dependencyQueue [$injName][] = $depName;
                        }
                    }
                }

                // Each module that has requested this dependency before it was initiated ($dependencyQueue)
                if(!empty($dependencyQueue[$depName]) && count($dependencyQueue[$depName]) > 0) {
                    foreach($dependencyQueue[$depName] as $injectIn) {
                        if(self::VERBOSE) {
                            echo " >> Late-Injecting $depName into $injectIn\n";
                        }
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
     * @return \Dice\Dice
     */
    static public function getFactory() {
        $factory = new \Dice\Loader\Json();
        $factory = $factory->load('conf/bootstrap.json');
        return $factory;
    }

    /**
     * Sets up the environment in early stages of execution. Bare minimum environment checks should go here.
     * @return void
     */
    static public function Sunrise() {
        if(self::VERBOSE) {
            echo "Hubbub session started " . date('r') . "\n";
        }

        // HHVM does not set REQUEST_TIME, guarantee it is there:
        if(!isset($_SERVER['REQUEST_TIME'])) {
            $_SERVER['REQUEST_TIME'] = time();
        }

        // All errors + strict
        error_reporting(E_ALL | E_STRICT);

        // Check if we're running a web instance
        if(php_sapi_name() != 'cli') {
            header('Content-Type: text/plain');
            ob_implicit_flush();

            if(self::VERBOSE) {
                echo "I think I am running in a web environment.  I normally need to be run in a shell.  I will continue anyway, but please be advised this might be a bad idea.\n";
            }
        }
    }

    /**
     * Clean up the environment in a graceful shutdown situation
     */
    static public function Sunset() {
        if(self::VERBOSE) {
            echo "Hubbub session gracefully shut down " . date('r') . "\n";
        }
    }

}