<?php
 /*
  * This file is a part of Hubbub, freely available at http://hubbub.sf.net
  *
  * Copyright (c) 2013, Armond B. Carroll <ben@hl9.net>
  * For full license terms, please view the LICENSE.txt file that was
  * distributed with this source code.
  */

	trait generic_irc {
		function parse_irc_hostmask($mask) {
			// RFC: <prefix>   ::= <servername> | <nick> [ '!' <user> ] [ '@' <host> ]
			$r = array();

			// Drop the : if we still have it
			if(substr($mask, 0, 1) == ':') {
				$mask = substr($mask, 1);
			}
			if(strpos($mask, '!') !== false) {
				list($r['nick'], $mask) = explode('!', $mask);
			}
			if(strpos($mask, '@') !== false) {
				list($r['user'], $mask) = explode('@', $mask);
				if(substr($r['user'], 0, 1) == '~') {
					$r['ident'] = false;
					$r['user'] = substr($r['user'], 1); // Drop the ~
					$r['user_tidle'] = substr($r['user'], 1);
				} else {
					$r['ident'] = true;
					$r['user_tidle'] = $r['user'];
				}
			}
			// TODO possibly use a regex (or something better..)
			// to determine if it's a nick OR host.  To my knowledge,
			// it's far more likely to be a host at this point, but according
			// to the prototype, it /could/ be a nick

			// Possibly just check for a '.' and it's a server, but that
			// still doesn't work 100% of the time if for example, something
			// is in /etc/hosts ..

			// Also, should host == server, or should 'host' be only for 'nick' and
			// server be for server?

			$r['host'] = $mask;
			return $r;
		}

		// Note, for simplicity, you must only pass 1 irc protocol line at a time,
		function parse_irc_cmd($c) {
			$r = array('raw' => $c);
			$c = trim($c);

			// For messages with a sender ("prefix" in irc rfc)
			if(substr($c, 0, 1) == ':') {
				list($r['sender'], $r['cmd'], $r['parm']) = explode(' ', $c, 3);
				$r['hostmask'] = $this->parse_irc_hostmask($r['sender']);
			} else {
				list($r['cmd'], $r['parm']) = explode(' ', $c, 2);
			}

			$r['cmd_original'] = $r['cmd'];
			if(is_numeric($r['cmd'])) {
				$numeric_swap = irc_numeric_cmd_swap($r['cmd']);
				if($numeric_swap !== false) { // note the !== although there should never be a 000
					$r['cmd_numeric'] = $r['cmd'];
					$r['cmd'] = $numeric_swap;
				} else {
					cout(owarning, "I don't have a definition for numeric command {$r['cmd']}");
				}
			}

			$r['cmd'] = strtolower($r['cmd']); // Not very IRC like, but I prefer it.

			// Now per-cmd processing rules
			if($r['cmd'] == 'privmsg') {
				list($r['privmsg']['to'], $r['privmsg']['msg']) = explode(' ', $r['parm'], 2);
				if(substr($r['privmsg']['msg'], 0, 1) == ':') {
					$r['privmsg']['msg'] = substr($r['privmsg']['msg'], 1);
				}

				// Check for ctcp marker
				if(substr($r['privmsg']['msg'], 0, 1) == chr(1) && substr($r['privmsg']['msg'], -1) == chr(1)) {
					$r['ctcp']['raw'] = substr($r['privmsg']['msg'], 1, strlen($r['privmsg']['msg']) - 2);
					if(strpos($r['ctcp']['raw'], ' ') !== false) {
						list($r['ctcp']['cmd'], $r['ctcp']['parm']) = explode(' ', $r['ctcp']['raw'], 2);
					} else {
						$r['ctcp']['cmd'] = $r['ctcp']['raw'];
						$r['ctcp']['parm'] = '';
					}
				}
			}

			return $r;
		}

		/* --- --- --- IRC Protocol Implemention (Outbound) --- ---- --- */
		// Ping & Ping are defined in 4.6[.2 & .3] of rfc1459
		// The chapter information accompanying each section below
		// is in reference to the old 1495 spec.

		function ping($server) {
			if(is_array($server)) {
				$this->send("PING " . implode(' ', $server));
			} else {
				$this->send("PING $server");
			}
		}

		function pong($server) {
			if(is_array($server)) {
				$this->send("PONG " . implode(' ', $server));
			} else {
				$this->send("PONG $server");
			}
		}

		// 4.1 Message Details

		function pass($pass) {
			if($state != 'unregistered') {
				cout(owarning, "Sending PASS in a non-unregistered state ({$this->state})");
			}
			$this->send('PASS ' . $pass);
		}

		function nick($nick) {
			$this->send('NICK ' . $nick);
		}

		function user($username, $realname) { // note we drop <hostname> and <servername> since we'll be "locally connected"
			$this->send('USER ' . $username . ' 0 0 :' . $realname);
		}

		// People really use this?
		function oper($username, $password) {
			$this->send('OPER ' . $username . ' ' . $password);
		}

		function quit($msg = "Gone to have lunch") {
			$this->send('QUIT :' . $msg);
		}

		// 4.2 Channel operations
		function join($channel, $key = '') {
			// TODO check if we're already in that channel
			// TODO maintain channel list that we're actively involvedi n
			if(!empty($key)) {
				$this->send("JOIN $channel $key");
			} else {
				$this->send("JOIN $channel");
			}
		}

		function part($channel) {
			// TODO maintain channel list that we're actively involved in
			$this->send("PART $channel");
		}

		// TODO: MODE command

		//function topic() { // ?? should we make a single function

		function set_topic($channel, $topic) {
			$this->send("TOPIC $channel :$topic");
		}

		function get_topic($channel) {
			$this->send("TOPIC $channel");
		}

		// TODO: NAMES command

		// TODO: LIST command

		// Note the paramters are reversed here than the protocol parameters
		function invite($channel, $user) {
			$this->send("INVITE $user $channel");
		}

		function kick($channel, $user, $comment = "") {
			if(empty($comment)) {
				$this->send("KICK $channel $user");
			} else {
				$this->send("KICK $channel $user :$comment");
			}
		}

		function server_version($server = false) {
			if(!$server) {
				$this->send("VERSION");
			} else {
				$this->send("VERSION $server");
			}
		}

		function server_time($server = false) {
			if(!$server) {
				$this->send("TIME");
			} else {
				$this->send("TIME $server");
			}
		}

		// Can STATS support more than one query type at once?
		// If so, maybe transform this into a higher level function ...
		function server_stats($query, $server = false) {
			if(!$server) {
				$this->send("STATS $query");
			} else {
				$this->send("STATS $query $server");
			}
		}

		function server_admin($server = false) {
			if(!$server) {
				$this->send("ADMIN");
			} else {
				$this->send("ADMIN $server");
			}
		}

		function server_info($server = false) {
			if(!$server) {
				$this->send("INFO");
			} else {
				$this->send("INFO $server");
			}
		}

		// 4.4 PRIVMSG and NOTICE
		function privmsg($who, $what) {
			$this->send("PRIVMSG $who :$what");
		}

		function send_ctcp($who, $what) {
			$this->notice($who, chr(1).$what.chr(1));
		}

		function notice($who, $what) {
			$this->send("NOTICE $who :$what");
		}

		// 4.5 User Based Queries

		// TODO Skipping WHO command
		// TODO Skipping WHOIS command
		// TODO Skipping WHOWAS command
		// NOTFIXING Skipping KILL command


		// 5. Optionals

		function away($msg = '') {
			if(empty($msg)) {
				$msg = 'Away';
			}
			$this->is_away = true;
			$this->send('AWAY ' . $msg);
		}

		function unaway() {
			$this->is_away = false;
			$this->send('AWAY');
		}

		// Not implemented here: REHASH, RESTART, SUMMON, WALLOPS, USERHOST, ISON,

		// Implement later: USERS
	}
