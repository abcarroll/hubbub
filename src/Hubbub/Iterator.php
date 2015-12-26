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

/**
 * Class Iterator
 *
 * @package Hubbub
 */
class Iterator {

    protected $modules;
    protected $run = true;

    /**
     * @param Configuration|null $conf   The configuration object
     * @param Logger|null        $logger The logger object
     */
    public function __construct(\Hubbub\Configuration $conf = null, \Hubbub\Logger $logger = null) {
        $this->conf = $conf;
        $this->logger = $logger;
    }

    /**
     * @param \Hubbub\Iterable $module An iterable object to add to the iteration stack.
     * @param string           $alias  An alias to use that we can reference the object with later.
     */
    public function add(\Hubbub\Iterable $module, $alias = null) {
        if($alias !== null) {
            $this->modules[$alias] = $module;
        } else {
            $this->modules[] = $module;
        }
    }

    /**
     * Removes the passed object alias from the iteration stack.
     *
     * @param string $alias The named alias
     *
     * @return bool True if the alias was found and removed, false if the alias was not found.
     */
    public function removeByAlias($alias) {
        if(isset($this->modules[$alias])) {
            unset($this->modules[$alias]);

            return true;
        } else {
            return false;
        }
    }

    /**
     * Removes the passed object reference from the iteration stack.
     *
     * @param \Hubbub\Iterable $module An iterable object to remove.
     *
     * @return bool
     */
    public function removeByObject(\Hubbub\Iterable $module) {
        $search = array_search($module, $this->modules, true);
        if($search !== false) {
            unset($this->modules[$search]);

            return true;
        } else {
            return false;
        }
    }

    /**
     * @return bool True if the run was ended 'gracefully', false if the iterator ran out of subjects to iterate over.
     */
    public function run() {
        while($this->run) {
            if(count($this->modules) > 0) {
                /** @var \Hubbub\Iterable $m */
                foreach($this->modules as $m) {
                    //$this->logger->debug("Iterating module: " . get_class($m));
                    $m->iterate();
                }
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * @return int Count of modules currently in the iterator.
     */
    public function count() {
        return count($this->modules);
    }

    /**
     * Returns an array with all current items in the iterator, with the aliases (or numeric index) as the key, and the class name as the value.
     *
     * @return array An array of all the current items in the iterator.
     */
    public function getItems() {
        $itemList = [];
        foreach($this->modules as $key => $object) {
            $itemList[$key] = get_class($object);
        }

        return $itemList;
    }
}