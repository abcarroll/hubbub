<?php

/*
 * Just a concept of CLI based configuration.
 */

$fields = [
    'irc-client' => [
        '_'        => 'Setup an IRC client to a single server.',
        'nickname' => 'Nickname',
        'username' => 'Username',
        'realname' => 'Real Name',
        'server'   => 'Server Block'
    ],
    'irc-bnc'    => [
        '_'              => 'Setup our lightweight IRC server to act as a BNC',
        'login-nickname' => 'Login Nickname',
        'login-password' => 'Login Password',
        'port'           => 'Port Number',
        'server'         => 'Listen IP Address? (blank for all)',
    ],
    /* 'xmpp' => [
        '_' => 'XMPP Works for Gtalk, Facebook, and More'
    ],
    'skype' => [
        '_' => 'Setup Skype Access through Dbus and a running skype instance',
    ],

    */
];

$setup = [];

while (1) {
    echo "What's next? \n";
    $z = 0;
    $z_table = [];
    foreach ($fields as $f => $x) {
        $z_table[$z] = '$f';
        $z++;
        echo " ($z) -> $f: {$x['_']}\n";
    }

    $z++;
    echo "$z -> write:  write it and return to his menu\n";
    echo "===> ";
    echo fgets(STDIN);
}
