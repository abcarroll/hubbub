<?php
    /*
     * This file is a part of Hubbub, freely available at http://hubbub.sf.net
     *
     * Copyright (c) 2013, Armond B. Carroll <ben@hl9.net>
     * For full license terms, please view the LICENSE.txt file that was
     * distributed with this source code.
     */

    // See https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md
    class logger {
        public function emergency($message, array $context = array()) {
            $this->log('emergency', $message, $context);
        }

        public function alert($message, array $context = array()) {
            $this->log('alert', $message, $context);
        }

        public function critical($message, array $context = array()) {
            $this->log('critical', $message, $context);
        }

        public function error($message, array $context = array()) {
            $this->log('error', $message, $context);
        }

        public function warning($message, array $context = array()) {
            $this->log('warning', $message, $context);
        }

        public function notice($message, array $context = array()) {
            $this->log('notice', $message, $context);
        }

        public function info($message, array $context = array()) {
            $this->log('info', $message, $context);
        }

        public function debug($message, array $context = array()) {
            $this->log('debug', $message, $context);
        }

        public function log($level, $message, array $context = array()) {
            echo "[" . date('h:i:sA') . "] [$level] $message\n";
        }

        public function __construct($parent) {

        }
    }
