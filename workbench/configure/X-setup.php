<?php

/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2013, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */

abstract class setup {
    public function __construct() {
        if(!is_readable('var/cfg')) {
            $this->early_setup();
        }
    }

    // Must be overridden in the implementation
    abstract public function ask($question, $answer = []);

    abstract public function say($statement);

    function early_setup() {
        $this->say("This is your first run.  You are required to answer some very basic questions, just once, to get up and running");
        $setup_bnc = $this->ask("Do you want to run an IRC BNC?  If so, you can finish your setup there once we figure out what port you want to run on.");
    }
}

class cli_setup extends setup {

    function say($statement) {
        echo ansi_esc('color/bold', $statement . "\n");
    }

    function ask($question, $answer_format = []) {
        echo ansi_esc('color/blue', 'Q: ' . $question) . "\n";

        if(in_array('bool', $answer_format)) {
            if(in_array('default=yes', $answer_format)) {
                $default = true;
            } elseif(in_array('default=no', $answer_format)) {
                $default = false;
            }
        }

        do {
            echo " -> ";
            ansi_esc('color/green');
            $answer = fgets(STDIN);
            echo ansi_esc();
            echo "\n";

            if(in_array('bool', $answer_format)) {
                $answer = strtolower($answer);
                if($answer == 'yes' || $answer == 'y') {
                    $validated = true;
                    $answer = true;
                } elseif($answer == 'no' || $answer == 'n') {
                    $validated = true;
                    $answer = false;
                }
            }

        } while (!isset($default) && $validated == false);


    }
}

new cli_setup();
die;
