<?php

namespace Hubbub\DNS;

/**
 * Returns the hostnema as-passed for name resolution requests, forcing the internal DNS system to handle it instead.
 *
 * This if for systems that does not have 'dig' installed or perhaps not supported at all.  It will force php to handle
 * the resolution normally at the function call level.  This WILL cause the script to hang while the resolution takes
 * place.  Most of this time this will not be a problem, but it will cause everything to hang up for a few seconds if
 * you have problems with name resolution.  For people who know you run Hubbub, this could be used to DOS your instance.
 * Use for testing / development only.
 *
 * @package Hubbub\DNS
 */
class NullResolver {
    protected $bus;

    public function __construct(\Hubbub\MessageBus $bus, $name) {
        $this->bus = $bus;
    }


    public function handleBusMessage($msg) {
        if(!(isset($msg['protocol']) && $msg['protocol'] == 'dns')) {
            return $msg;
        }

        // Handle action=resolve
        if(isset($msg['action']) && $msg['action'] == 'resolve') {
            $this->bus->publish([
                'protocol' => 'dns',
                'action' => 'response',
                'host' => $msg['host'],
                'status' => 'ok',
                'message' => 'The hostname was not resolved becasuse you are using the NullResolver'
            ]);
        }


        return $msg;
    }

}