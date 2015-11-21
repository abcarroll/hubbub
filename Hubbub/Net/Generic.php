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

namespace Hubbub\Net;

    /**
     * @todo Expand this interface's documentation.
     */

/**
 * Interface Generic
 *
 * @package Hubbub\Net
 */
interface Generic {
    /**
     * @return mixed
     */
    function iterate();

    /**
     * @param $mode
     *
     * @return mixed
     */
    function set_blocking($mode);
}