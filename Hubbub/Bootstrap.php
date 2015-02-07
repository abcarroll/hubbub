<?php

namespace Hubbub;

class Bootstrap {
    static public function loadDependencies() {
        // This injects everything with everything.
        // Something something .. Like this ..


        require 'conf/bootstrap.php';
        if(!empty($bootstrap)) {

            $dependencies = [];

            // Initialize
            foreach($bootstrap as $depName => $depCfg) {
                echo " > Bootstrapping $depName as {$depCfg['class']}\n";
                $dependencies[ $depName ] = new $depCfg['class']();

                #print_r($dependencies);

                // Inject as many dependencies as we can that are already initiated
                foreach($depCfg['inject'] as $injName) {
                    if(isset($dependencies[ $injName ]) && method_exists($dependencies[ $injName ], 'set' . $injName)) {
                        echo " >  Injecting $injName into $depName\n";
                        $dependencies[ $injName ]->{'set' . $injName}($dependencies[ $injName ]);
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

}