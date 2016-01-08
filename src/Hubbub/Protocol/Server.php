<?php
/*
 * This file is a part of Hubbub, available at:
 * http://github.com/abcarroll/hubbub
 *
 * Copyright (c) 2013-2015, A.B. Carroll <ben@hl9.net>
 * Hubbub is distributed under a BSD-like license.
 *
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code, or available at the URL above.
 */

namespace Hubbub\Protocol;

/**
 * Interface Server
 * @package Hubbub\Protocol
 */
interface Server {
    /**
     * @param $clientId int Socket ID for the newly connected socket
     *
     * @return void
     */
    function on_client_connect($clientId);

    /**
     * @param $clientId int Socket ID for the recently disconnected socket
     *
     * @return void
     */
    function on_client_disconnect($clientId);

    /**
     * @param $clientId int    Socket ID for the client sending the data
     * @param $data     string The data that is being sent
     *
     * @return void
     */
    function on_client_send($clientId, $data);

    /**
     * @param $clientId int    The Socket ID for the client that received the data.
     * @param $data     string The data received. This could be a full, or partial message.
     *
     * @return mixed
     */
    function on_client_recv($clientId, $data);
}