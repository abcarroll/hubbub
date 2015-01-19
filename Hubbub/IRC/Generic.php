<?php

/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2013, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */

namespace Hubbub\IRC;

/**
 * Class Generic
 *
 * @package Hubbub\Modules\IRC
 */
trait Generic {
    private $state; // unregistered, registered, ??

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
            $numeric_swap = $this->irc_numerics[(int) $r['cmd']];

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

    /* --- --- --- IRC Protocol Implementation (Outbound) --- ---- --- */
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
        if($this->state != 'unregistered') {
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
        $this->notice($who, chr(1) . $what . chr(1));
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

    /* This is meant to override on_recv() in various net_* classes */
    function on_recv($data) {
        $commands = explode("\n", $data);
        if(!empty($commands[count($commands) - 1])) {
            $incomplete = $commands[count($commands) - 1];
            $commands[count($commands) - 1] = '';
            //$this->hubbub->logger->warning("Received incomplete command '$incomplete' - discarding");
            trigger_error("Received incomplete command '$incomplete' - discarding");
        }

        foreach ($commands as $c) {
            if(!empty($c)) {
                $this->on_recv_irc($c);
            }
        }
    }

    // TODO Make it work backwards, too
    function numeric_convert($convert) {
        return $this->irc_numerics[$convert];
    }


    /* Keep this at the bottom.  It's far too long of a table
      to have to scroll through.  There should be no further codee
      at the bottom of this file */

    /* RPL_BOUNCE_OR_RPL_ISUPPORT is the only exception,
      RPL_BOUNCE is, I believe, mostly deprecated and fell out of use.  
      RPL_ISUPPORT is more of the norm.
      
      It is possible to tell which in all cases by looking at registration status.
      RPL_BOUNCE should always be pre-registration (pre 001/rpl_welcome)
      While post-registration it would always indiate a RPL_ISUPPORT
    */

    private $irc_numerics = [
        001 => 'RPL_WELCOME',
        002 => 'RPL_YOURHOST',
        003 => 'RPL_CREATED',
        004 => 'RPL_MYINFO',
        005 => 'RPL_BOUNCE_OR_RPL_ISUPPORT',
        010 => 'RPL_BOUNCE_MODERN',
        302 => 'RPL_USERHOST',
        303 => 'RPL_ISON',
        301 => 'RPL_AWAY',
        305 => 'RPL_UNAWAY',
        306 => 'RPL_NOWAWAY',
        311 => 'RPL_WHOISUSER',
        312 => 'RPL_WHOISSERVER',
        313 => 'RPL_WHOISOPERATOR',
        317 => 'RPL_WHOISIDLE',
        318 => 'RPL_ENDOFWHOIS',
        319 => 'RPL_WHOISCHANNELS',
        314 => 'RPL_WHOWASUSER',
        369 => 'RPL_ENDOFWHOWAS',
        321 => 'RPL_LISTSTART',
        322 => 'RPL_LIST',
        323 => 'RPL_LISTEND',
        325 => 'RPL_UNIQOPIS',
        324 => 'RPL_CHANNELMODEIS',
        331 => 'RPL_NOTOPIC',
        332 => 'RPL_TOPIC',
        341 => 'RPL_INVITING',
        342 => 'RPL_SUMMONING',
        346 => 'RPL_INVITELIST',
        347 => 'RPL_ENDOFINVITELIST',
        348 => 'RPL_EXCEPTLIST',
        349 => 'RPL_ENDOFEXCEPTLIST',
        351 => 'RPL_VERSION',
        352 => 'RPL_WHOREPLY',
        315 => 'RPL_ENDOFWHO',
        353 => 'RPL_NAMREPLY',
        366 => 'RPL_ENDOFNAMES',
        364 => 'RPL_LINKS',
        365 => 'RPL_ENDOFLINKS',
        367 => 'RPL_BANLIST',
        368 => 'RPL_ENDOFBANLIST',
        371 => 'RPL_INFO',
        374 => 'RPL_ENDOFINFO',
        375 => 'RPL_MOTDSTART',
        372 => 'RPL_MOTD',
        376 => 'RPL_ENDOFMOTD',
        381 => 'RPL_YOUREOPER',
        382 => 'RPL_REHASHING',
        383 => 'RPL_YOURESERVICE',
        391 => 'RPL_TIME',
        392 => 'RPL_USERSSTART',
        393 => 'RPL_USERS',
        394 => 'RPL_ENDOFUSERS',
        395 => 'RPL_NOUSERS',
        200 => 'RPL_TRACELINK',
        201 => 'RPL_TRACECONNECTING',
        202 => 'RPL_TRACEHANDSHAKE',
        203 => 'RPL_TRACEUNKNOWN',
        204 => 'RPL_TRACEOPERATOR',
        205 => 'RPL_TRACEUSER',
        206 => 'RPL_TRACESERVER',
        207 => 'RPL_TRACESERVICE',
        208 => 'RPL_TRACENEWTYPE',
        209 => 'RPL_TRACECLASS',
        210 => 'RPL_TRACERECONNECT',
        261 => 'RPL_TRACELOG',
        262 => 'RPL_TRACEEND',
        211 => 'RPL_STATSLINKINFO',
        212 => 'RPL_STATSCOMMANDS',
        219 => 'RPL_ENDOFSTATS',
        242 => 'RPL_STATSUPTIME',
        243 => 'RPL_STATSOLINE',
        221 => 'RPL_UMODEIS',
        234 => 'RPL_SERVLIST',
        235 => 'RPL_SERVLISTEND',
        251 => 'RPL_LUSERCLIENT',
        252 => 'RPL_LUSEROP',
        253 => 'RPL_LUSERUNKNOWN',
        254 => 'RPL_LUSERCHANNELS',
        255 => 'RPL_LUSERME',
        256 => 'RPL_ADMINME',
        259 => 'RPL_ADMINEMAIL',
        263 => 'RPL_TRYAGAIN',
        401 => 'ERR_NOSUCHNICK',
        402 => 'ERR_NOSUCHSERVER',
        403 => 'ERR_NOSUCHCHANNEL',
        404 => 'ERR_CANNOTSENDTOCHAN',
        405 => 'ERR_TOOMANYCHANNELS',
        406 => 'ERR_WASNOSUCHNICK',
        407 => 'ERR_TOOMANYTARGETS',
        408 => 'ERR_NOSUCHSERVICE',
        409 => 'ERR_NOORIGIN',
        411 => 'ERR_NORECIPIENT',
        412 => 'ERR_NOTEXTTOSEND',
        413 => 'ERR_NOTOPLEVEL',
        414 => 'ERR_WILDTOPLEVEL',
        415 => 'ERR_BADMASK',
        421 => 'ERR_UNKNOWNCOMMAND',
        422 => 'ERR_NOMOTD',
        423 => 'ERR_NOADMININFO',
        424 => 'ERR_FILEERROR',
        431 => 'ERR_NONICKNAMEGIVEN',
        432 => 'ERR_ERRONEUSNICKNAME',
        433 => 'ERR_NICKNAMEINUSE',
        436 => 'ERR_NICKCOLLISION',
        437 => 'ERR_UNAVAILRESOURCE',
        441 => 'ERR_USERNOTINCHANNEL',
        442 => 'ERR_NOTONCHANNEL',
        443 => 'ERR_USERONCHANNEL',
        444 => 'ERR_NOLOGIN',
        445 => 'ERR_SUMMONDISABLED',
        446 => 'ERR_USERSDISABLED',
        451 => 'ERR_NOTREGISTERED',
        461 => 'ERR_NEEDMOREPARAMS',
        462 => 'ERR_ALREADYREGISTRED',
        463 => 'ERR_NOPERMFORHOST',
        464 => 'ERR_PASSWDMISMATCH',
        465 => 'ERR_YOUREBANNEDCREEP',
        466 => 'ERR_YOUWILLBEBANNED',
        467 => 'ERR_KEYSET',
        471 => 'ERR_CHANNELISFULL',
        472 => 'ERR_UNKNOWNMODE',
        473 => 'ERR_INVITEONLYCHAN',
        474 => 'ERR_BANNEDFROMCHAN',
        475 => 'ERR_BADCHANNELKEY',
        476 => 'ERR_BADCHANMASK',
        477 => 'ERR_NOCHANMODES',
        478 => 'ERR_BANLISTFULL',
        481 => 'ERR_NOPRIVILEGES',
        482 => 'ERR_CHANOPRIVSNEEDED',
        483 => 'ERR_CANTKILLSERVER',
        484 => 'ERR_RESTRICTED',
        485 => 'ERR_UNIQOPPRIVSNEEDED',
        491 => 'ERR_NOOPERHOST',
        501 => 'ERR_UMODEUNKNOWNFLAG',
        502 => 'ERR_USERSDONTMATCH',
        /* These are reserved for future use, deprecated, etc */
        231 => 'RPL_SERVICEINFO',
        232 => 'RPL_ENDOFSERVICES',
        233 => 'RPL_SERVICE',
        300 => 'RPL_NONE',
        316 => 'RPL_WHOISCHANOP',
        361 => 'RPL_KILLDONE',
        362 => 'RPL_CLOSING',
        363 => 'RPL_CLOSEEND',
        373 => 'RPL_INFOSTART',
        384 => 'RPL_MYPORTIS',
        213 => 'RPL_STATSCLINE',
        214 => 'RPL_STATSNLINE',
        215 => 'RPL_STATSILINE',
        216 => 'RPL_STATSKLINE',
        217 => 'RPL_STATSQLINE',
        218 => 'RPL_STATSYLINE',
        240 => 'RPL_STATSVLINE',
        241 => 'RPL_STATSLLINE',
        244 => 'RPL_STATSSLINE',
        246 => 'RPL_STATSPING',
        247 => 'RPL_STATSBLINE',
        250 => 'RPL_STATSDLINE',
        492 => 'ERR_NOSERVICEHOST',
    ];

}


/*
 * This is just 100% junk that needs to be sorted out:
 * Removed from a file that was in the repository called numeric_codes.php
 */

/* Never access $numeric codes directly, always use numeric_code() */
/* See PROTOCOL_NOTES.txt for information about RPL_BOUNCE / RPL_ISUPPORT, and why
    you need to pass the registration status to numeric_code() */

/*
$numeric_code_by_number = array(
    001 => 'RPL_WELCOME',
    002 => 'RPL_YOURHOST',
    003 => 'RPL_CREATED',
    004 => 'RPL_MYINFO',
    005 => 'RPL_BOUNCE_OR_RPL_ISUPPORT',
    010 => 'RPL_BOUNCE_MODERN',
    302 => 'RPL_USERHOST',
    303 => 'RPL_ISON',
    301 => 'RPL_AWAY',
    305 => 'RPL_UNAWAY',
    306 => 'RPL_NOWAWAY',
    311 => 'RPL_WHOISUSER',
    312 => 'RPL_WHOISSERVER',
    313 => 'RPL_WHOISOPERATOR',
    317 => 'RPL_WHOISIDLE',
    318 => 'RPL_ENDOFWHOIS',
    319 => 'RPL_WHOISCHANNELS',
    314 => 'RPL_WHOWASUSER',
    369 => 'RPL_ENDOFWHOWAS',
    321 => 'RPL_LISTSTART',
    322 => 'RPL_LIST',
    323 => 'RPL_LISTEND',
    325 => 'RPL_UNIQOPIS',
    324 => 'RPL_CHANNELMODEIS',
    331 => 'RPL_NOTOPIC',
    332 => 'RPL_TOPIC',
    341 => 'RPL_INVITING',
    342 => 'RPL_SUMMONING',
    346 => 'RPL_INVITELIST',
    347 => 'RPL_ENDOFINVITELIST',
    348 => 'RPL_EXCEPTLIST',
    349 => 'RPL_ENDOFEXCEPTLIST',
    351 => 'RPL_VERSION',
    352 => 'RPL_WHOREPLY',
    315 => 'RPL_ENDOFWHO',
    353 => 'RPL_NAMREPLY',
    366 => 'RPL_ENDOFNAMES',
    364 => 'RPL_LINKS',
    365 => 'RPL_ENDOFLINKS',
    367 => 'RPL_BANLIST',
    368 => 'RPL_ENDOFBANLIST',
    371 => 'RPL_INFO',
    374 => 'RPL_ENDOFINFO',
    375 => 'RPL_MOTDSTART',
    372 => 'RPL_MOTD',
    376 => 'RPL_ENDOFMOTD',
    381 => 'RPL_YOUREOPER',
    382 => 'RPL_REHASHING',
    383 => 'RPL_YOURESERVICE',
    391 => 'RPL_TIME',
    392 => 'RPL_USERSSTART',
    393 => 'RPL_USERS',
    394 => 'RPL_ENDOFUSERS',
    395 => 'RPL_NOUSERS',
    200 => 'RPL_TRACELINK',
    201 => 'RPL_TRACECONNECTING',
    202 => 'RPL_TRACEHANDSHAKE',
    203 => 'RPL_TRACEUNKNOWN',
    204 => 'RPL_TRACEOPERATOR',
    205 => 'RPL_TRACEUSER',
    206 => 'RPL_TRACESERVER',
    207 => 'RPL_TRACESERVICE',
    208 => 'RPL_TRACENEWTYPE',
    209 => 'RPL_TRACECLASS',
    210 => 'RPL_TRACERECONNECT',
    261 => 'RPL_TRACELOG',
    262 => 'RPL_TRACEEND',
    211 => 'RPL_STATSLINKINFO',
    212 => 'RPL_STATSCOMMANDS',
    219 => 'RPL_ENDOFSTATS',
    242 => 'RPL_STATSUPTIME',
    243 => 'RPL_STATSOLINE',
    221 => 'RPL_UMODEIS',
    234 => 'RPL_SERVLIST',
    235 => 'RPL_SERVLISTEND',
    251 => 'RPL_LUSERCLIENT',
    252 => 'RPL_LUSEROP',
    253 => 'RPL_LUSERUNKNOWN',
    254 => 'RPL_LUSERCHANNELS',
    255 => 'RPL_LUSERME',
    256 => 'RPL_ADMINME',
    259 => 'RPL_ADMINEMAIL',
    263 => 'RPL_TRYAGAIN',
    401 => 'ERR_NOSUCHNICK',
    402 => 'ERR_NOSUCHSERVER',
    403 => 'ERR_NOSUCHCHANNEL',
    404 => 'ERR_CANNOTSENDTOCHAN',
    405 => 'ERR_TOOMANYCHANNELS',
    406 => 'ERR_WASNOSUCHNICK',
    407 => 'ERR_TOOMANYTARGETS',
    408 => 'ERR_NOSUCHSERVICE',
    409 => 'ERR_NOORIGIN',
    411 => 'ERR_NORECIPIENT',
    412 => 'ERR_NOTEXTTOSEND',
    413 => 'ERR_NOTOPLEVEL',
    414 => 'ERR_WILDTOPLEVEL',
    415 => 'ERR_BADMASK',
    421 => 'ERR_UNKNOWNCOMMAND',
    422 => 'ERR_NOMOTD',
    423 => 'ERR_NOADMININFO',
    424 => 'ERR_FILEERROR',
    431 => 'ERR_NONICKNAMEGIVEN',
    432 => 'ERR_ERRONEUSNICKNAME',
    433 => 'ERR_NICKNAMEINUSE',
    436 => 'ERR_NICKCOLLISION',
    437 => 'ERR_UNAVAILRESOURCE',
    441 => 'ERR_USERNOTINCHANNEL',
    442 => 'ERR_NOTONCHANNEL',
    443 => 'ERR_USERONCHANNEL',
    444 => 'ERR_NOLOGIN',
    445 => 'ERR_SUMMONDISABLED',
    446 => 'ERR_USERSDISABLED',
    451 => 'ERR_NOTREGISTERED',
    461 => 'ERR_NEEDMOREPARAMS',
    462 => 'ERR_ALREADYREGISTRED',
    463 => 'ERR_NOPERMFORHOST',
    464 => 'ERR_PASSWDMISMATCH',
    465 => 'ERR_YOUREBANNEDCREEP',
    466 => 'ERR_YOUWILLBEBANNED',
    467 => 'ERR_KEYSET',
    471 => 'ERR_CHANNELISFULL',
    472 => 'ERR_UNKNOWNMODE',
    473 => 'ERR_INVITEONLYCHAN',
    474 => 'ERR_BANNEDFROMCHAN',
    475 => 'ERR_BADCHANNELKEY',
    476 => 'ERR_BADCHANMASK',
    477 => 'ERR_NOCHANMODES',
    478 => 'ERR_BANLISTFULL',
    481 => 'ERR_NOPRIVILEGES',
    482 => 'ERR_CHANOPRIVSNEEDED',
    483 => 'ERR_CANTKILLSERVER',
    484 => 'ERR_RESTRICTED',
    485 => 'ERR_UNIQOPPRIVSNEEDED',
    491 => 'ERR_NOOPERHOST',
    501 => 'ERR_UMODEUNKNOWNFLAG',
    502 => 'ERR_USERSDONTMATCH',
    // These are supposedly reserved for future use, deprecated, etc
    231 => 'RPL_SERVICEINFO',
    232 => 'RPL_ENDOFSERVICES',
    233 => 'RPL_SERVICE',
    300 => 'RPL_NONE',
    316 => 'RPL_WHOISCHANOP',
    361 => 'RPL_KILLDONE',
    362 => 'RPL_CLOSING',
    363 => 'RPL_CLOSEEND',
    373 => 'RPL_INFOSTART',
    384 => 'RPL_MYPORTIS',
    213 => 'RPL_STATSCLINE',
    214 => 'RPL_STATSNLINE',
    215 => 'RPL_STATSILINE',
    216 => 'RPL_STATSKLINE',
    217 => 'RPL_STATSQLINE',
    218 => 'RPL_STATSYLINE',
    240 => 'RPL_STATSVLINE',
    241 => 'RPL_STATSLLINE',
    244 => 'RPL_STATSSLINE',
    246 => 'RPL_STATSPING',
    247 => 'RPL_STATSBLINE',
    250 => 'RPL_STATSDLINE',
    492 => 'ERR_NOSERVICEHOST',
);


$numeric_code_by_cmd = array_flip($numeric_code_by_number);

// Registration status may be passed to better handle RPL_BOUNCE / RPL_ISUPPORT
function numeric_code($code_or_cmd, $registration_status = -1) {
    if(is_int($code_or_cmd){
			global $numeric_code_by_number;

    return $numeric_code_by_number[$code_or_cmd];
} elseif
    (is_string($code_or_cmd)) {
        global $numeric_code_by_cmd;

        return $numeric_code_by_cmd[$code_or_cmd];
    } else {
        return false;
    }
	}

// TODO handle backwards port ranges (e.g. passing an array and getting 6667, 6668, 7000-8000)
function port_range($str) {
    $return = array();
    // remove any characters except digits ',' and '-'
    $str = preg_replace('/[^\d,-]/', '', $str);
    // split by ,
    $ports = explode(',', $str);
    if(!is_array($ports)) {
        $ports = array($ports);
    }
    foreach ($ports as $p) {
        $p = explode('-', $p);
        if(count($p) > 1) {
            for ($x = $p[0]; $x <= $p[1]; $x++) {
                $return[] = $x;
            }
        } else {
            $return[] = $p[0];
        }
    }

    return $return;
}*/