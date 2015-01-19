<?php

/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2013, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */

/**
 * Interface net_generic_client
 */
interface net_generic_client extends net_generic {
    /**
     * @param $data
     *
     * @return mixed
     */
    function send($data);

    /**
     * @param $length
     *
     * @return mixed
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