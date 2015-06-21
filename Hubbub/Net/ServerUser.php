<?php

/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2015, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */

namespace Hubbub\Net;

interface ServerUser {

    public function on_listen();
    public function on_client_connect($client);
    public function on_client_disconnect($client);
    public function on_send($client, $data);
    public function on_recv($client, $data);

}