<?php
 /*
  * This file is a part of Hubbub, freely available at http://hubbub.sf.net
  *
  * Copyright (c) 2013, Armond B. Carroll <ben@hl9.net>
  * For full license terms, please view the LICENSE.txt file that was
  * distributed with this source code.
  */

	interface net_generic_server extends net_generic {
		function send($socket, $data);
		function recv($socket, $length);		
		function on_client_connect($socket);
  	function on_client_disconnect($socket);
  	function on_client_send($socket, $data);
  	function on_client_recv($socket, $data);
  }