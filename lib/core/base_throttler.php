<?php
 /*
  * This file is a part of Hubbub, freely available at http://hubbub.sf.net
  *
  * Copyright (c) 2013, Armond B. Carroll <ben@hl9.net>
  * For full license terms, please view the LICENSE.txt file that was
  * distributed with this source code.
  */

	class base_throttler {
		protected $hub, $frequency, $iteration, $modules = [];

		function __construct($hub, $frequency) {
			$this->hub = $hub;
			$this->frequency = $frequency;
		}

		function add_module(module $obj) {
			trigger_error("Loaded new module");
			$this->modules[] = $obj;
		}

		function throttle() {
			$this->iteration++;
		}
	}