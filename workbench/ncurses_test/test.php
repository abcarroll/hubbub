<?php
/*
 * Licensed under CC Attribution 3.0 Unported
 *
 * Code taken from 'm dot quinton at gmail dot com', from:
 * http://php.net/function.ncurses-getmouse.php
 * Thank you for your code example.
 */

error_reporting(E_ALL);

function win($w, $h, $x, $y, $txt) {
    // now lets create a small window
    $win = ncurses_newwin($w, $h, $x, $y);
    // border our small window.
    ncurses_wborder($win, 0, 0, 0, 0, 0, 0, 0, 0);
    # ncurses_wrefresh($win);// paint both windows
    ncurses_refresh(); // paint both windows

    // move into the small window and write a string
    ncurses_mvwaddstr($win, 0, 1, " $txt ");
    ncurses_mvwaddstr($win, 1, 1, "($w, $h, $x, $y)");

    // show our handiwork and refresh our small window
    ncurses_wrefresh($win);

    return $win;
}

// Initialie ncurses
$ncurse = ncurses_init();
ncurses_noecho();
// A full screen window

$win0 = win(0, 0, 0, 0, 'win0 ZERO');
$win1 = win(10, 30, 7, 25, 'win1 ONE');
$win2 = win(10, 30, 20, 25, 'win2 TWO');
$info = win(15, 20, 2, 2, 'info INFO');

// Draw everything so far
// ncurses_refresh();

$newmask = NCURSES_BUTTON1_CLICKED + NCURSES_BUTTON1_RELEASED;
# $newmask = NCURSES_ALL_MOUSE_EVENTS;

$mask = ncurses_mousemask($newmask, $oldmask);
$events = array();

while (1) {

    ncurses_wmove($info, 1, 1);
    $ch = ncurses_getch();

    ncurses_wclear($info);
    ncurses_refresh(); // paint both windows
    ncurses_wborder($info, 0, 0, 0, 0, 0, 0, 0, 0);
    ncurses_refresh(); // paint both windows
    ncurses_mvwaddstr($win0, 0, 1, " info MAMBO ");
    ncurses_refresh(); // paint both windows

    switch ($ch) {

        case NCURSES_KEY_MOUSE:

            if(ncurses_getmouse($mevent)) {
                $events[] = $mevent;

                ncurses_mvwaddstr($info, 2, 1, " mouse event   ");
                ncurses_mvwaddstr($info, 3, 1, " ({$mevent['x']}/{$mevent['y']}) ");
                ncurses_mvwaddstr($info, 4, 1, " ({$mevent['mmask']}) ");

                ncurses_wrefresh($info);
            }
            break;

        case chr('q'):
            break 2;

        default:

            if($ch > 0x40)
                $txt = chr($ch) . " $ch ";
            else
                $txt = '.' . " $ch";

            ncurses_mvwaddstr($info, 1, 1, " $txt   ");
            if($ch == 58) {
                ncurses_move(10, 10);
                ncurses_addstr("Hello World");
            }

            ncurses_wrefresh($info);

    }

    if(chr($ch) == 'q')
        break;
}

ncurses_end(); // clean up our screen

print_r($events);
