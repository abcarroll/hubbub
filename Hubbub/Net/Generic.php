<?php

/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2013, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
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