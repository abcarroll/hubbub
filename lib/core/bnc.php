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

		public function __construct($hubub) {
			$this->hubbub = $hubub;
			parent::__construct("tcp://0.0.0.0:7777");
		}

		function send_irc($socket, $data) {
			$data .= "\r\n";
			socket_server::send($socket, $data);
		}

		function on_client_connect($socket) {
			$this->read_sockets[$socket] = $socket;
			$this->send($socket, "NOTICE AUTH :*** Welcome to Hubbub/git");
		}

		function on_client_disconnect($socket) {

		}
		
		function on_client_recv($socket, $data) { }
		function on_client_send($socket, $data) { }
		
		
		function on_iterate() { 
			$fakeworkusec = mt_rand(0,300000);
			usleep($fakeworkusec);
			
			echo "BNC was iterated, did nothing for $fakeworkusec uSec\n";
			
			print_r(get_included_files());
		}
		
		
	}