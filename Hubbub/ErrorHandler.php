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
/**
 * @todo phpDoc blocks
 */

namespace Hubbub;

class ErrorHandler {
    /**
     * @var \Hubbub\Logger
     */
    protected $logger;

    // Ordered from lowest to highest
    static protected $errNoToString = [
        E_ERROR             => "Fatal Error",
        E_WARNING           => "Warning",
        E_PARSE             => "Parse Error",
        E_NOTICE            => "Notice",
        E_CORE_ERROR        => "Core Error",
        E_CORE_WARNING      => "Core Warning",
        E_COMPILE_ERROR     => "Compile Error",
        E_COMPILE_WARNING   => "Compile Warning",
        E_USER_ERROR        => "App-Level Error",
        E_USER_WARNING      => "App-Level Warning",
        E_USER_NOTICE       => "App-Level Notice",
        E_STRICT            => "Strict Notice",
        E_RECOVERABLE_ERROR => "Recoverable Error",
        E_DEPRECATED        => "Deprecated Notice",
        E_USER_DEPRECATED   => "App-Level Deprecated Notice",
        // E_ALL ...
    ];

    // These might could be improved.
    static protected $errNoToLevel = [
        E_ERROR             => "emergency",
        E_WARNING           => "warning",
        E_PARSE             => "emergency",
        E_NOTICE            => "notice",
        E_CORE_ERROR        => "emergency",
        E_CORE_WARNING      => "emergency",
        E_COMPILE_ERROR     => "emergency",
        E_COMPILE_WARNING   => "emergency",
        E_USER_ERROR        => "emergency",
        E_USER_WARNING      => "warning",
        E_USER_NOTICE       => "notice",
        E_STRICT            => "debug",
        E_RECOVERABLE_ERROR => "warning",
        E_DEPRECATED        => "warning",
        E_USER_DEPRECATED   => "warning",
    ];

    public function __construct(\Hubbub\Logger $logger = null) {
        $this->logger = $logger;

        if($logger !== null) {
            $this->setLogger($logger);
        }
    }

    public function handle($errNo, $errStr, $errFile, $errLine, $errContext) {
        // This error code is not included in error_reporting
        if(!(error_reporting() & $errNo)) {
            return true;
        }

        $level = ErrorHandler::$errNoToLevel[$errNo];

        $message = "(php) [" . ErrorHandler::$errNoToString[$errNo] . "]: $errStr in $errFile:$errLine\n";

        $this->logger->log($level, $message, $errContext);

        switch($errNo) {
            case E_ERROR:
            case E_USER_ERROR:
            case E_RECOVERABLE_ERROR:
                throw new \ErrorException($errStr, 0, $errNo, $errFile, $errLine);
            break;
        }

        return true;
    }

    public function setHandler() {
        set_error_handler([$this, 'handle']);;
    }

    public function setLogger(\Hubbub\Logger $logger) {
        $this->logger = $logger;
        $this->setHandler();
    }
}