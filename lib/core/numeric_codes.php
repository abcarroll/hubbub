<?php
/* Never access $numeric codes directly, always use numeric_code() */
/* See PROTOCOL_NOTES.txt for information about RPL_BOUNCE / RPL_ISUPPORT, and why
    you need to pass the registration status to numeric_code() */

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
    /* These are supposedly reserved for future use, deprecated, etc */
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
}

?>