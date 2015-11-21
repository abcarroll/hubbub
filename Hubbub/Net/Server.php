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

interface Server {

    public function on_listen();

    public function on_client_connect($client);

    public function on_client_disconnect($client);

    public function on_send($client, $data);

    public function on_recv($client, $data);

}