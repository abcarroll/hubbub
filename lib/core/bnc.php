<?php
 /*
  * This file is a part of Hubbub, freely available at http://hubbub.sf.net
  *
  * Copyright (c) 2013, Armond B. Carroll <ben@hl9.net>
  * For full license terms, please view the LICENSE.txt file that was
  * distributed with this source code.
  */

	class bnc extends net_stream_server {
		protected $hubbub;
		protected $clients;
		
		public function __construct($hubub) {
			$this->hubbub = $hubub;
			parent::__construct("tcp://0.0.0.0:7777");
		}

		function on_client_connect($socket) {
			$this->clients[(int) $socket] = new bnc_client($this->hubbub, $this, $socket);
			$this->clients[(int) $socket]->iterate(); // Iterate once after connection automatically
		}

		function on_client_disconnect($socket) {
			unset($this->clients[(int) $socket]);
		}
		
		function on_client_recv($socket, $data) {
			$this->clients[(int) $socket]->on_recv($data);
		}
			
		function on_client_send($socket, $data) {
			/* this may be a moot method anyway.  we have no way of actually controlling
			   when data is sent. */
		}
		
		function on_iterate() { 
			if(count($this->clients) > 0) { 
				foreach($this->clients as $c) { 
					$c->iterate();
				}
			}
			$this->hubbub->logger->debug("BNC Server was iterated with " . count($this->clients) . " clients");
		}
	}
