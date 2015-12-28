<?php
$networkList = [];

$lines = file('servlist_.conf');
$current = [];
foreach($lines as $line) {
    $line = trim($line);
    if(!empty($line)) {
        list($type, $data) = explode('=', $line, 2);
        $type = strtolower($type);
        if($type == 'n' && count($current) > 0) {
            $networkList[] = $current;
            $current = [];
        }

        switch($type) {
            case 'n':
                $current['network'] = $data;
            break;
            case 'e':
                $current['charset'] = $data;
            break;
            case 's':
                if(!isset($current['servers'])) {
                    $current['servers'] = [];
                }
                $current['servers'][] = $data;
            break;
            default:
                // not sure what 'f' and 'd' are
                $current[$type] = $data;
        }
    }
}

echo "Parsed " . count($networkList) . " servers from xchat-compatible server list.\n\n";

// This will generate a Hubbub-compatible config

foreach($networkList as $net) {
    $networkName = strtolower($net['network']);
    $networkName = str_replace([' ', "'", '"'], ['-', '', ''], $networkName);
    echo "\$conf['hubbub']['$networkName'] = '\\Hubbub\\IRC\\Client';\n";
    echo "\$conf['irc']['$networkName'] = ['serverList' => [";
    $sBuffer = '';
    foreach($net['servers'] as $s) {
        $count = 0;
        $s = str_replace('/', ':', $s, $count);
        if($count <= 0) {
            $s .= ':6667';
        }
        $sBuffer .= "'$s', ";
    }
    $sBuffer = substr($sBuffer, 0, -2);
    echo $sBuffer . "]]; \n\n";
}