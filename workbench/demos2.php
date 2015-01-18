<?php

/*
{
     proto,
     network-type, {  }
     network,
     to,
     from,
     event,
     parameter(s),
}

*/


gc_disable();

function mem_usage() {
    return round((memory_get_usage(1) / 1024), 2) . ' KB';
}

class environment {

    private $gc_enabled;

    function __construct() {
        $this->garbage_collection();
    }

    function garbage_collection() {
        if(gc_enabled()) {
            $this->gc_enabled = true;
            trigger_error("PHP Automatic garbage collection enabled.");
        } else {
            $this->gc_enabled = false;
            trigger_error("PHP Automatic garbage collection disabled.");
        }
    }
}

$environment = new environment();


abstract class module {
    private $module_uid;

    abstract public function notify(array $event);

    public function __toString() {
        if(empty($this->module_uid)) {
            $this->module_uid = md5(microtime(1));
        }

        return $this->module_uid;
    }
}

class irc_client extends module {
    private $observer;

    function __construct(observer $observer) {
        $this->observer = $observer;

        $observer->bus->notify(['proto' => 'irc', 'event' => 'connected', 'msg' => 'Constructed a new irc client']);
    }

    function notify(array $event) {
        echo "(client) Notified about:";
        print_r($event);
    }

    function tickle() {
        $this->observer->bus->notify(['proto' => 'irc', 'event' => 'connected', 'msg' => 'IRC Client was Tickled']);
    }
}

class xmpp_client extends module {
    private $observer;

    function __construct(observer $observer) {
        $this->observer = $observer;

        $observer->bus->notify(['proto' => 'xmpp', 'event' => 'connected', 'msg' => 'Constructed a new xmpp client']);
    }

    function notify(array $event) {
        echo "(client) Notified about:";
        print_r($event);
    }

    function tickle() {
        $this->observer->bus->notify(['proto' => 'xmpp', 'event' => 'connected', 'msg' => 'xmpp client was tickled']);
    }
}

class irc_server extends module {
    private $observer;

    function __construct(observer $observer) {
        $this->observer = $observer;
        $this->observer->bus->subscribe($this, ['proto' => 'xmpp']);
    }

    function notify(array $event) {
        echo "(server) Notified about:";
        print_r($event);
    }

}


class micro_bus {
    private $subscriptions = [];
    private $subscription_objects = [];

    function notify(array $event) {
        return $this->publish($event);
    }

    function publish(array $event) {
        foreach ($this->subscriptions as $sObj => $sFilter) {
            foreach ($sFilter as $fKey => $fVal) {
                if($event[$fKey] != $fVal) {
                    break 2; // Some field didn't match, break from that subscription
                }
            }
            $this->subscription_objects[$sObj]->notify($event);
        }
    }

    function subscribe(module $obj, array $filter = []) {
        $this->subscriptions[(string) $obj] = $filter;
        $this->subscription_objects[(string) $obj] = $obj;
    }

    function unsubscribe(module $obj, array $filter = []) {
        unset($this->subscriptions[$obj]);
    }
}

abstract class observer {

}

class moonshine extends observer {
    public $bus = [], $modules = [];

    function __construct() {
        $this->bus = new micro_bus();

        $modules[] = new irc_client($this);
        $modules[] = new xmpp_client($this);
        $modules[] = new irc_client($this);
        $modules[] = new xmpp_client($this);
        $modules[] = new irc_client($this);
        $modules[] = new irc_server($this);

        for ($x = 0; $x < 10; $x++) {
            $z = $x % 4;
            #$modules[$z]->tickle();
        }
    }
}

$moonshine = new moonshine();

/*
    - A Key Value Store, Possibly Storing JSON Encoded Data
    - A Simple File Storage Setup, For Log Files, Etc
*/

interface data_store {
    function set($key, $data);

    function write($key, $data);

    function set_string($str, $data);


}


/*
    Possible strategies:
        - Adjusting Delay:  Iterations occur at set intervals, regardless of how long the iteration occurs.  If the iteration frequency is 100ms, we perform, and say it took 20ms, we sleep for 80ms.
        - Fixed Delay: There is a fixed delay between each interval.  If an iteration takes 20ms, we sleep for 100ms.  If an iteration takes 100ms, we sleep for 100ms.  If an iteration takes 1ms, we sleep for 100ms.
        - Fixed Distrubuted Socket Blocking: Each socket receives 1/Nth of the iteration interval as timeout, where N=# of sockets
        - Adjusting Distributed Socket Blocking: Same as above, but adjusts as in Adjusting Delay, uses previous cycle time to pull ahead/pull back
        - CPU Based:  Set min/max cpu for process, min/max cpu for system,
            [


            ]
*/

class dummy {
    function iterate() {
        #usleep(mt_rand(0,mt_rand(10000,mt_rand(30000,90000))));
    }
}

class base_iterator {
    protected $frequency, $iteration, $modules = [];

    function __construct($frequency) {
        $this->frequency = $frequency;
    }

    function add_module(module $obj) {
        trigger_error("Loaded new module");
        $this->modules[] = $obj;
    }

    function iterate() {
        $this->iteration++;

        foreach ($this->modules as $m) {
            $m->iterate();
        }
    }

}

class fixed_delay_iterator extends base_iterator {
    function iterate() {
        parent::iterate();
        trigger_error("[{$this->iteration}] Sleeping for {$this->frequency}");
        usleep($this->frequency);
    }
}

class adjusting_delay_iterator extends base_iterator {

    function iterate() {
        $iteration_start = microtime(1);

        parent::iterate();

        $iteration_length = round((microtime(1) - $iteration_start) * 1000000);
        $iteration_sleep = $this->frequency - $iteration_length;
        if($iteration_sleep > 0) {
            usleep($iteration_sleep);
            trigger_error("[{$this->iteration}] Sleeping for $iteration_sleep uSec, iteration took $iteration_length uSec");
        } else {
            trigger_error("[{$this->iteration}] NOT Sleeping, iteration took $iteration_length uSec");
        }
    }
}


$s = microtime(1);
for ($x = 0; $x < 1000000; $x++) {
    /*[
        'proto' => 'xx',
        'network_type' => 'xx',
        'network' => 'xx',
        'to' => 'xx',
        'from' => 'xx',
        'event' => 'xx',
        'parameter' => 'xx'
    ];*/

    #new event('xx','xx','xx','xx','xx','xx','xx');
}
printf("Took %f seconds", (microtime(1) - $s));


die;


class observer {
    private $children = [];

    function __construct() {
        for ($x = 0; $x < 200; $x++) {
            $this->children[] = new observee($this);
        }
    }

    function loop() {
        foreach ($this->children as $child) {
            $child->loop();
        }
    }
}

class observee {
    private $observer;

    function __construct(Observer $observer) {
        $this->observer = $observer;
    }

    function loop() {
        echo ".";

        for ($x = 0; $x < 100; $x++) {
            mt_rand();
        }

    }
}

$observer = new observer();

$t = microtime(1);
while (1) {
    $observer->loop();
    usleep(100000);
}
printf("%f", (microtime(1) - $t));
echo "\n\n";
