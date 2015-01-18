<?php

/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2013, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */

interface net_generic_client extends net_generic {
    function send($data);

    function recv($length);

    function on_connect();

    function on_disconnect();

    function on_send($data);

    function on_recv($data);
}