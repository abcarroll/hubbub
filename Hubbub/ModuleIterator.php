<?php

/*
 * @todo  Probably write an interface such as IterableModule
 */

 class ModuleIterator {

     protected $modules;

     public function add($module, $alias = null) {
         if($alias !== null) {
            $this->modules[$alias] = $module;
         } else {
            $this->modules[] = $module;
         }
    }

     public function removeByObject($module) {

     }

     public function removeByAlias($alias) {
        unset($this->modules[$alias]);
     }

     public function run() {
         while (1) {
             if(count($this->modules) > 0) {
                 foreach ($this->modules as $m) {
                     $m->iterate();
                 }
             } else {
                throw new \Exception("No modules to iterate!");
             }
         }
     }
 }