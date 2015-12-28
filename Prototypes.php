<?php
/**
 * Just an example architecture for reference.
 *
 * This file is meant to be a reference architecture / example to show how Hubbub might work.  That is, a high level overview of either how Hubbub might
 * currently work or how it may work in the future.  This code is not meant to be used by any other packages.
 *
 * @copyright A.B. Carroll <ben@hl9.net>
 * @package Hubbub/Prototype
 */

namespace Hubbub\Prototype;

// Iterator, Cycler, Poller, PollTaker, ModuleList, ThreadIterator, ThreadQueue,
interface IteratorInterface {
    public function add(string $alias, ModuleInterface $module);

    public function removeByAlias(string $alias);

    public function removeByObject(ModuleInterface $module);

    public function count();

    public function getItemClasses();
}

// Iterable, Cycleable, Pollable, Module, Thread, RunnableThread, WorkThread,
interface RunnableInterface {
    public function runWork();
}


interface ConfInterface {
    public function get($key);
}

interface LoggerInterface {
    public function emergency($message, array $context = array());

    public function alert($message, array $context = array());

    public function critical($message, array $context = array());

    public function error($message, array $context = array());

    public function warning($message, array $context = array());

    public function notice($message, array $context = array());

    public function info($message, array $context = array());

    public function debug($message, array $context = array());

    public function log($level, $message, array $context = array());
}

// MessageBus, Bus,... this might could also act as a queue
interface MessageBusInterface {
    public function subscribe();

    public function unsubscribe();

    public function broadcast();

    public function publish();

    public function send();
}

// Throttler shouldn't really be anything special, just a normal module thingy that sleeps
interface Throttler extends RunnableInterface {

}

// Do connectionless protocols ever block?
interface Net_Interface {
    public function setBlocking($doesBlock);
}

interface Net_ClientInterface extends NetInterface {

}