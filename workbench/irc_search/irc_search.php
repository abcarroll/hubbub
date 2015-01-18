<?php




$f = file_get_contents('index.html');
preg_match('/<table class="netlist" width="100%">(.*)<\/table>/ism', $f, $m);
$netlist_block = $m[1];

preg_match_all('/<a.*class="(?:competitor|applicant|maverick)".*>(.*)<\/a>/ismU', $netlist_block, $m);

function str_distance_array($needle, $haystack) {
    $confidence_map = [];
    foreach ($haystack as $h) {
        if(strlen($needle) < strlen($h)) {
            $normalize = strlen($needle);
        } else {
            $normalize = strlen($h);
        }

        $confidence_map[$h] = $normalize - (levenshtein($needle, $h) * 2);
        $confidence_map[$h] += similar_text($needle, $h);
    }

    natsort($confidence_map);
    $confidence_map = array_reverse($confidence_map);

    return $confidence_map;
}

print_r(str_distance_array('asper net', $m[1]));


die;
if(!is_readable('servers.ini')) {
    file_put_contents('servers.ini', file_get_contents('http://www.mirc.com/servers.ini'));
}


if(is_readable('servers.ini')) {
    $mirc_servers = file("servers.ini");
} else {
    die("Couldn't find servers.ini\nExiting\n\n");
}


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






?>
		
