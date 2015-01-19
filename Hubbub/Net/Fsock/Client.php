<?php

/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2013, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */

namespace Hubbub\Net\Fsock;

/**
 * This is meant to be an implementation of the generic client using
 * highly generic fsockopen().  It is a candidate for removal.
 *
 * @todo Evaluate probability of this being useful or ever implemented, and either remove it, or implement it.
 */

class Client implements \Hubbub\Net\Generic\Client {
    function send($data) {}
    function recv($length) {}
    function on_connect() {}
    function on_disconnect() {}
    function on_send($data) {}
    function on_recv($data) {}
    function set_blocking($mode) { }
    function iterate() { }
}
