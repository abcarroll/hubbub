<?php
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