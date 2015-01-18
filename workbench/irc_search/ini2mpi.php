<?php
/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2015, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */

/*
 * Originally came from an unofficial version of mpiBot
 * Converted to mpiBot's configuration syntax.  However it does show how to
 * 1) Retrieve server list from that website below
 * 2) Parse it
 */

if(!is_readable('servers.ini')) {
    file_put_contents('servers.ini', file_get_contents('http://www.mirc.com/servers.ini'));
}


if(is_readable('servers.ini')) {
    $mirc_servers = file("servers.ini");
} else {
    die("Couldn't find servers.ini\nExiting\n\n");
}

$output_array = array();

$start_processing = false;
foreach ($mirc_servers as $mline) {
    $mline = trim($mline);
    if(empty($mline)) {
        continue;
    } elseif(!$start_processing) {
        if($mline == '[servers]') {
            $start_processing = true;
        }
        continue;
    }

    // Else, we're processing
    list(, $mline) = explode('=', $mline, 2);
    list($label, $mline) = explode('SERVER:', $mline, 2);
    list($server, $mline) = explode(":", $mline, 2);
    list($ports, $network) = explode("GROUP:", $mline, 2);

    $output_array[strtolower($network)][] = "$server:$ports";

    //echo "$network $server $ports ($label)\n";
}

file_put_contents('networks.conf.php', "<?php\n\t \$cfg['irc']['network_servers'] = array_merge(" . var_export($output_array, true) . ", \$cfg['irc']['network_servers']);\n?>");

echo "\nProcessing done. If you don't see parse errors next, all went well.";

if(!isset($cfg['irc']['network_servers'])) $cfg['irc']['network_servers'] = array();
require __DIR__ . DIRECTORY_SEPARATOR . 'networks.conf.php';

echo "\nExiting.\n\n";

?>
		
