<?php

namespace Hubbub\Protocol;

/*
 * This object receives notifications from a \Net\Client object about connections, disconnections, data being sent and received
 */

interface Client {
    public function on_connect();
    public function on_disconnect();
    public function on_send($data);
    public function on_recv($data);
}