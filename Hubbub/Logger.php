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
 * A logger interface that "looks like" the PSR-3 interface.
 * Note it does not technically, but practically, implements PsrLogLoggerInterface.
 * See https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md
 *
 * @todo Actual PSR-3 usage.
 */

/**
 * Class Logger
 *
 * @package Hubbub
 */
class Logger { // extends PsrLogAbstractLogger implements PsrLogLoggerInterface {
    /**
     * @var Logger $instance
     * @var Configuration $conf
     * @var Resource $fp
     */
    protected $instance, $conf, $fp;

    //public function __construct($parent, $config) {
    public function __construct(\Hubbub\Configuration $conf = null) {
        if($conf !== null) {
            $this->setConf($conf);
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
            fwrite($this->fp, $logText);
        }

        // Print a "context dump" (think core dump) on severe errors
        switch($level) {
            case 'emergency':
            case 'alert':
            case 'critical':
            case 'error':
            case 'warning':
                $this->dumpContext($logText, $context);
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

    public function setConf(\Hubbub\Configuration $conf) {
        $this->conf = $conf;

        if(!empty($conf['logToFile'])) {
            $this->fp = fopen($conf['logToFile'], 'a+');
        } else {
            $this->fp = null;
        }
    }

    /**
     * Returns the instance of the object in a Singleton pattern.
     * This is only meant for \Hubbub\ErrorHandler.
     *
     * @return Logger
     */
    public static function getInstance() {
        static $instance = null;
        if ($instance === null) {
            $instance = new Logger();
        }
        return $instance;
    }

}
