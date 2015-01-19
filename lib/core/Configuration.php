<?php
/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2015, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */

# namespace Hubbub;

/*
 * Warning: Logger is not available at this point! (FIXME)
 *
 * I am not impressed with this jumble of code.
 */

class Configuration {
    private $data;

    public function __construct($hubbub) {
        include 'local-config.php';
        $this->data = (object) $config;
    }

    public function getData() {
        return $this->data;
    }


}