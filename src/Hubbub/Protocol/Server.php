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
     * @param $socket
     * @param $data
     *
     * @return mixed
     */
    //function send($socket, $data);

    /**
     * @param $socket resource
     * @param $length int
     *
     * @return mixed
     */
    //function recv($socket, $length);

    /**
     * @param $socket resource The newly connected socket resource
     *
     * @return void
     */
    function on_client_connect($socket);

    /**
     * @param $socket resource The recently disconnected socket resource
     *
     * @return void
     */
    function on_client_disconnect($socket);

    /**
     * @param $socket resource The socket that is sending data
     * @param $data   string The data that is being sent
     *
     * @return void
     */
    function on_client_send($socket, $data);

    /**
     * @param $socket resource The client that received the data.
     * @param $data   string The data received. This data may be binary or ASCII text.
     *
     * @return mixed
     */
    function on_client_recv($socket, $data);
}