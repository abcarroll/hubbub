<?php
/*
 * Example static config file: Will be switched out for something more dynamic eventually.
 */

/*
 * Just some interesting stuff to possibly jog our memory later on why we chose the ->get('dotted.notation') method of configuration values.
 * The other options were;
 *  - as an array ($this->config['some']['value'])
 *  - as an object ($this->config->some->value)
 *
 * While 'as an object' seems like a great idea, ideally the idea there being each major module type would have something like an abstract or interface
 * to it's configuration value (e.x. class IrcConfiguration { function getNickname(); function getQuitMsg(); .. etc) .. This also seems like a hell of a lot of
 * extra work and also seems like it would be making things a bit difficult to plan for.  So perhaps the ->get() with dotted notation can be a common standard
 * but discouraged in the future.
 */

require '../conf/local-config.php';

class fakeStdObject {
    private $storage;

    function __construct($input) {
        $this->storage = $input;
    }

    public function __get($x) {
        if(isset($this->jsonObject->$x)) {
            return $this->jsonObject->$x;
        } /*elseif(isset($this->jsonObject->{$this->rootpoint}[0]->$x)) {
            return $this->jsonObject->{$this->rootpoint}[0]->$x;
        } */ else return null;
    }

}

$conf = json_encode($conf);
$conf = json_decode($conf);

var_dump($conf);
die;

$conf = new fakeStdObject($conf);

var_dump($conf->irc->nickname);

die;

