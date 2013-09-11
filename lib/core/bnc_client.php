<?php
 /*
  * This file is a part of Hubbub, freely available at http://hubbub.sf.net
  *
  * Copyright (c) 2013, Armond B. Carroll <ben@hl9.net>
  * For full license terms, please view the LICENSE.txt file that was
  * distributed with this source code.
  */

	class bnc_client { // extends ??, this is a bit new..
		use generic_irc;

		private $hubbub, $bnc, $socket;
		private $connect_time, $state = 'preauth';

		function __construct($hubbub, $bnc, $socket) { 
			$this->hubbub = $hubbub;
			$this->bnc = $bnc;
			$this->socket = $socket;
			$this->notice("AUTH", "I'm going to have to ask you see your I.D.");
			$this->notice("AUTH", "Type /QUOTE PASS <yourpass> now.");
		}

		function disconnect() { 
			fclose($this->socket); // sorta works
		}

		function send($command) {
			$this->bnc->send($this->socket, "$command\n");
		}

		function on_recv($data) { 
			$commands = explode("\n", $data);
			if(!empty($commands[count($commands)-1])) { 
				$incomplete = $commands[count($commands)-1];
				$commands[count($commands)-1] = '';
				$this->hubbub->logger->warning("Received incomplete command '$incomplete' - discarding");
			}
			foreach($commands as $c) { 
				if(!empty($c)) { 
					$this->on_recv_irc($c);
				}
			}
		}

		function on_recv_irc($c) { 
			$c = $this->parse_irc_cmd($c);

			if($c['cmd'] == 'pass') { 
				$compare = trim(file_get_contents('var/passwd'));
				if($c['parm'] == $compare) { 
					$this->state = 'online';
					$this->notice("AUTH", "Welcome home!");
				} else {
					$this->hubbub->logger->notice("Failed login, {$c['parm']} != $compare"); 
					$this->disconnect(); // Not implemented, WILL cause fatal error.
				}
			} else { 
				$this->hubbub->logger->warning("Unhandled IRC Command: {$c['cmd']}");
			}
		}

		function iterate() {
			$this->hubbub->logger->debug("BNC Client #" . ((int)$this->socket) . " was iterated.");
		}
	}
