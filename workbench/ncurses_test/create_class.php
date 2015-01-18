<?php
$f = file('ncurses.txt');
foreach ($f as $l) {
    $l = trim($l);

    if(empty($l)) {
        continue;
    }

    list($function,) = explode('—', $l);
    $function = trim($function);
    $inner_function = explode('_', $function, 2);

    $function_d = str_replace("_", '-', $function);
    $f = file_get_contents("php-chunked-xhtml/function.$function_d.html");
    preg_match('/<div class="methodsynopsis dc-description">(.*)<\/div>/ismU', $f, $synopsis);
    $synopsis = strip_tags($synopsis[1]);
    $synopsis = str_replace("\r", "", $synopsis);
    $synopsis = str_replace("\n", "", $synopsis);
    $synopsis = trim(preg_replace("/ +/", " ", $synopsis));
    $synopsis = str_replace("( ", "(", $synopsis);
    $synopsis = str_replace(" )", ")", $synopsis);

    // machine synopsis, don't handle void error conditions, throws some errors, but this is a one time script
    preg_match('/\((.*)\)/', $synopsis, $msynopsis_m);
    $msynopsis = @explode(',', $msynopsis_m[1]);
    foreach ($msynopsis as &$sy) {
        $sy = trim($sy);
        $sy_p = explode(' ', $sy, 2);
        $sy = trim($sy_p[1]);
    }
    $msynopsis = implode(', ', $msynopsis);

    preg_match('/<span class="dc-title">(.*)<\/span>/ismU', $f, $desc);
    $desc = trim($desc[1]);

    $msynopsis_no_ref = str_replace("&", "", $msynopsis);

    echo "\t\t// $synopsis - $desc\n";
    echo "\t\tpublic function " . $inner_function[1] . "($msynopsis) {\n\t\t\treturn $function($msynopsis_no_ref);\n\t\t} ";
    echo "\n\n";
}


