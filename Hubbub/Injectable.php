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

namespace Hubbub;

class Injectable {
    /** @var  Configuration */
    public $conf;

    /** @var  Logger */
    public $logger;

    /** @var  MessageBus */
    public $bus;

    public function inject($property, $value) {
        if(property_exists($this, $property)) {
            $this->$property = $value;
        }
    }

    public function setConf($conf) {
        $this->inject('conf', $conf);
    }

    public function setLogger($logger) {
        $this->inject('logger', $logger);
    }

    public function setBus($bus) {
        $this->inject('bus', $bus);
    }
}