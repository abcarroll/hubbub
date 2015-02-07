<?php

/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2013, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */

namespace Hubbub;

    /**
     * This is meant to be a unified method of storing long term data for modules.  It is only a placeholder.
     *
     * @todo Clean up this file, do something more than a placeholder.  Originally was called simple_data_storage.
     */

/**
 * Class StorageContainer
 *
 * @package Hubbub\Utility
 */
class StorageContainer extends \StdClass {
    function load() {

    }

    function save() {
        print_r((array) $this);
    }
}