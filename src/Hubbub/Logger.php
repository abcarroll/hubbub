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
     * A logger interface that "looks like" the PSR-3 interface.
     * Note it does not technically, but practically, implements PsrLogLoggerInterface.
     * See https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md
     *
     * @todo Actual PSR-3 usage.
     * @todo phpDoc Updates
     */

/**
 * Class Logger
 *
 * @package Hubbub
 */
class Logger { // extends PsrLogAbstractLogger implements PsrLogLoggerInterface {
    /**
     * @var Logger        $instance
     * @var Configuration $conf
     * @var Resource      $fp
     */
    protected $conf, $fp;

    public function __construct(\Hubbub\Configuration $conf = null) {
        $this->conf = $conf;
        if(!empty($this->conf->get('logger.logToFile'))) {
            $this->fp = fopen($this->conf->get('logger.logToFile'), 'a+');
        } else {
            $this->fp = null;
        }
    }

    public function __destruct() {
        if($this->fp) {
            fclose($this->fp);
        }
    }

    public function log($level, $message, array $context = array()) {
        // Generate a pretty message
        $prefixStr = "[" . date('h:i:sA') . "] [$level] ";
        $logText = $prefixStr . str_replace("\n", "\n$prefixStr", $message);

        echo $logText . "\n";
        if($this->fp) {
            fwrite($this->fp, $logText . "\n");
        }

        // Print a "context dump" (think core dump) on severe errors
        switch($level) {
            case 'emergency':
            case 'alert':
            case 'critical':
            case 'error':
            case 'warning':
                // TODO Make configurable.  Off until then, this is really meant more for long running instances.
                // $this->dumpContext($logText, $context);
            break;
        }
    }

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

    /**
     * Generates and writes a "context dump" to the local working directory.
     *
     * @param       $logText
     * @param array $context
     */
    protected function dumpContext($logText, array $context) {
        $logText = date('r') . "\n\n" . $logText;
        ob_start();
        var_dump($context);
        $logText .= "\n\nContext Dump As Follows:\n\n";
        $logText .= ob_get_clean();

        $fileName = 'context-' . sha1($logText) . '.txt';

        if(@file_put_contents($fileName, $logText)) {
            echo "\n* * * Context dump written to file: $fileName *\n\n";
        } else {
            fwrite(STDERR, "\n** ** ** Could not write context dump to file: $fileName *\n\n");
        }
    }
}
