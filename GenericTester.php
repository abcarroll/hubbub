<?php
// PSR-4 autoloader
spl_autoload_register(function ($class) {
    $file = __DIR__ . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    if(file_exists($file)) {
        require $file;
    }
});


class IRCTester {
    // use Hubbub\IRC\Numerics;
    use Hubbub\IRC\Parser;
    use Hubbub\IRC\Senders;

    function on_recv_irc($raw_data, $socket = null) {
        echo "$raw_data\n";
        $d = $this->parseIrcCommand($raw_data);
        // print_r($d);
    }

}

$irc = new IRCTester();


$inputData = file('xchat-raw2.log');
foreach ($inputData as $line) {
    if(substr($line, 0, 3) == '>> ') {
        $line = substr($line, 3);
    } else {
        continue;
    }
    $irc->on_recv($line);
}

$inputData = file('xchat-raw.log');
foreach ($inputData as $line) {
    if(substr($line, 0, 3) == '>> ') {
        $line = substr($line, 3);
    } else {
        continue;
    }
    $irc->on_recv($line);
}

$inputData = file('raw.log');
foreach ($inputData as $line) {
    $irc->on_recv($line);
}

