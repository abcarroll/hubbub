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
 * A simple logger that implements the PSR-3 LoggerInterface and logs internal Hubbub messages to file.
 *
 * @package Hubbub
 * @see     https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md
 * @see     https://bitbucket.org/leverton/mefworks-log.git A decent implementation of filtering
 */
class Logger extends \Psr\Log\AbstractLogger implements \Psr\Log\LoggerInterface {
    /**
     * @var Logger        $instance
     * @var Configuration $conf
     * @var Resource      $fp
     */
    protected $conf, $fp;

    /**
     * Logger constructor.
     *
     * @param Configuration|null $conf
     */
    public function __construct(\Hubbub\Configuration $conf = null) {
        $this->conf = $conf;
        if(!empty($this->conf->get('logger/logToFile'))) {
            $this->fp = fopen($this->conf->get('logger/logToFile'), 'a+');
        } else {
            $this->fp = null;
        }
    }

    public function __destruct() {
        if($this->fp) {
            fclose($this->fp);
        }
    }

    /**
     * Log with an arbitrary level.
     *
     * Currently, the only 'feature' is to log all output to both the 'logger.logToFile' configuration value as well as stdout.
     *
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     *
     * @return null
     *
     * @todo Implement filtering and obvious features.
     */
    public function log($level, $message, array $context = array()) {
        // Perform PSR-3 substitution
        foreach($context as $cKey => $cVal) {
            $message = str_replace("{$cKey}", $cVal, $message);
        }

        // Generate a pretty message
        $prefixStr = "[" . date('h:i:sA') . "] [$level] ";
        $logText = $prefixStr . str_replace("\n", "\n$prefixStr", $message);

        echo $logText . "\n";
        if($this->fp) {
            fwrite($this->fp, $logText . "\n");
        }

        if(isset($context['state-dump']) && $context['state-dump'] == true) {
            $this->dumpState($message, $context);
        }
    }

    /**
     * Generates and writes a "state dump" to the local working directory.
     *
     * Think core dump.
     *
     * @param       $logText
     * @param array $context
     */
    protected function dumpState($logText, array $context) {
        ob_start();
        var_dump($context);
        var_dump($GLOBALS);

        $logText = date('r') . "\n\nMessage: " . $logText;
        $logText .= "\n\nContext Dump As Follows:\n\n";
        $logText .= ob_get_clean();

        $fileName = 'context-' . sha1($logText) . '.txt';

        if(@file_put_contents(Utility::baseDir() . DIRECTORY_SEPARATOR . $fileName, $logText)) {
            echo "\n* * * Context dump written to file: $fileName *\n\n";
        } else {
            fwrite(STDERR, "\n** ** ** Could not write context dump to file: $fileName *\n\n");
        }
    }
}
