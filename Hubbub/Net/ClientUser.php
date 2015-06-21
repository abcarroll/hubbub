<?php

/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2015, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */

namespace Hubbub\Net;

interface ClientUser {

    public function on_connect();
    public function on_disconnect();
    public function on_send($data);
    public function on_recv($data);

}