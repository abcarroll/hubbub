<?php
// see:
// https://moquet.net/blog/distributing-php-cli/

// doesn't work, not PHP_INI_ALL or whatnot..
// ini_set('phar.readonly', 0);

$phar = new Phar("hubbub.phar",
    FilesystemIterator::CURRENT_AS_FILEINFO |
    FilesystemIterator::KEY_AS_FILENAME, "hubbub");


/*
$pharContents = [
    'src/',
    'start.php',
];

foreach($pharContents as $i) {
    if(is_dir($i)) {
        $phar->buildFromDirectory($i);
    } else {
        $phar[$i] = file_get_contents($i);
    }
}*/

$phar->buildFromDirectory('.');
$stub = $phar->createDefaultStub("start.php");
// but then we have to use explicit namespaces in start...
$phar->setStub("#!/usr/bin/env php\n" . $stub);