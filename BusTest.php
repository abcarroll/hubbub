<?php

class Subscription {

    /**
     * @var Bus
     * @var
     */
    protected
        /** @var  */$bus;

    protected $filter;

    protected $callback;

    /**
     * @param Bus      $bus
     * @param array    $filter
     * @param callable $callback
     */
    public function __construct(Bus $bus, array $filter, callable $callback) {
        $this->bus = $bus;
        $this->filter = $filter;
        $this->callback = $callback;

    }

    public function publish($message) {
        $this->bus->publish($message);
    }

    public function unsubscribe() {
        $this->bus->unsubscribe($this);
    }

}

class Bus {

    private $subscriptions = [];

    public function subscribe($filter, $callback) {
        $subscription = new Subscription($filter, $callback);
    }

    /*
     * Meant to be only called by Subscriptions?  How do I declare that?
     */
    public function unsubscribe(Subscription $subscription) {

        $key = array_search($subscription);

        if(isset($this->subscriptions[$key])) {
            unset($this->subscriptions[$key]);
            return true;
        } else {
            return false;
        }
    }

    public function publish($detail, $message) {
        foreach($this->subscriptions)

    }

}


$bus = new Bus();

$critera = [
    'server' => 'www',
];

$subscription = $bus->subscribe($critera);

$subscription->publish()