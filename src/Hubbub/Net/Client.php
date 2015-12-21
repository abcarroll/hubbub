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

namespace Hubbub\Net;

/*
 * this interface has switched roles - originally it was what is now the Protocol Client
 * now, it serves as an interface for \Net\Stream\Client and \Net\Socket\Client
 */

interface Client extends \Hubbub\Iterable {
    /**
     * @param \Hubbub\Protocol\Client $protocol The event handler object
     *
     * @return void
     */
    public function setProtocol(\Hubbub\Protocol\Client $protocol);

    /**
     * @param bool $mode True to set blocking, false to set non-blocking
     *
     * @return bool Whether or not the operation completed
     */
    public function set_blocking($mode);

    /**
     * @param $where
     *
     * @return bool
     */
    function connect($where);

    /**
     * @return bool
     */
    function disconnect();

    /**
     * @param $data
     *
     * @return mixed
     */
    function send($data);

    /**
     * @param $length
     *
     * @return bool|string The data received or false on failure.
     */
    function recv($length);

    /**
     * @return mixed
     */
    function on_connect();

    /**
     * @return mixed
     */
    function on_disconnect();

    /**
     * @param $data
     *
     * @return mixed
     */
    function on_send($data);

    /**
     * @param $data
     *
     * @return mixed
     */
    function on_recv($data);
}