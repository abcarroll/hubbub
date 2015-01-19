<?php
/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2013, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */

namespace Hubbub\Throttlers;

use Hubbub\Throttler\AdjustingDelay;

/**
 * This is an attempt to have the bot self-monitor it's cpu usage to automatically adjust the frequency. It is incomplete and BROKEN!
 *
 * Some Resources:
 * sysconf(3), time(7)
 * http://php.webtutor.pl/en/2011/05/13/how-to-calculate-cpu-usage-of-a-php-script/
 * http://stackoverflow.com/questions/1420426/calculating-cpu-usage-of-a-process-in-linux -- has /proc/<pid>/stat args
 * http://stackoverflow.com/questions/4189123/python-how-to-get-number-of-mili-seconds-per-jiffy (Multiple answers helpful)
 * https://github.com/rk4an/phpsysinfo
 * https://github.com/rk4an/phpsysinfo/blob/master/includes/os/class.Linux.inc.php
 * http://stackoverflow.com/questions/7538251/how-can-i-get-the-cpu-and-memory-useage - windows code at bottom
 * http://stackoverflow.com/questions/4705759/how-to-get-cpu-usage-and-ram-usage-without-exec - php sys info and linfo
 * http://colby.id.au/calculating-cpu-usage-from-proc-stat posix method thru /proc for whole sys
 *
 * Notes:
 * Last I checked, the python SC_CLK_TCK wasn't firing.  The SC_CLK_TCK isn't necessary if we PROPERLY grab total ticks from /proc/stat instead of calculating the assumed value ourselves using time * SC_CLK_TCK.
 * Note that /proc/stat might include "double" counts on some values in newer kernels.  This means the code we have isn't accurate.
 * Math is all wrong.
 * Include method for windows + php real time only (no /proc)
 * This was written when Hubbub used the Throttlers as the outright iterator / observer and thus the context may be slightly dated.
 */

trigger_error("The CPU-Adjusted Delay Throttler is likely broken!", E_USER_ERROR);

/**
 * Class CpuAdjustedDelay
 *
 * @package Hubbub\Throttlers
 */
class CpuAdjustedDelay extends AdjustingDelay {
    private $jiffy_sec;
    private $target_cpu = 10;


    private $total_sys_jiffies, $total_ujiff, $total_sjiff;

    function get_total_sys_jiffies() {
        $proc_stat = file('/proc/stat');
        foreach ($proc_stat as $line) {
            if(substr($line, 0, 4) == 'cpu ') {
                break; // correct line is now in $line
            }
        }

        $pieces = explode(' ', $line);
        $pieces[0] = 0; // overwrite cpu line

        $total_jiffies = array_sum($pieces);

        return $total_jiffies;
    }

    function __construct($hubbub, $frequency) {
        parent::__construct($hubbub, $frequency);

        // The ZZZ is just an easy way to make sure it returned an integer and nothing else (i.e. error)
        $try = shell_exec('python -c "import os; print \'ZZZ\'; print os.sysconf(os.sysconf_names[\'SC_CLK_TCK\'])"');
        if(substr($try, 0, 3) == 'ZZZ' && ($try = substr($try, 3)) && is_numeric($try)) {
            $this->jiffy_sec = $try;
        } else {
            trigger_error("Could not get definite answer from sysconf(3) _SC_CLK_TCK for jiffy value.  It should be always 100, however, so going with that.");
            $this->jiffy_sec = 100;
        }

        $this->total_sys_jiffies = $this->get_total_sys_jiffies();
    }

    #user_util = 100 * (utime_after - utime_before) / (time_total_after - time_total_before);
    #sys_util = 100 * (stime_after - stime_before) / (time_total_after - time_total_before);

    // bad math, come back when you have more sleep..
    function get_cpu_usage() {
        $pid = getmypid();
        $last_jiffies = $this->total_sys_jiffies;
        $this->total_sys_jiffies = $this->get_total_sys_jiffies();

        $stats = file_get_contents('/proc/' . $pid . '/stat');
        trigger_error($stats);
        $stats = explode(' ', $stats);

        $user = $stats[13];
        $sys = $stats[14];

        $new_user = 100 * ($user - $this->total_ujiff) / ($this->total_sys_jiffies - $last_jiffies);
        $new_sys = 100 * ($sys - $this->total_sjiff) / ($this->total_sys_jiffies - $last_jiffies);


        trigger_error("user: $new_user .. sys: $new_sys");
    }

    function iterate() {
        $this->get_cpu_usage();
        parent::iterate();
    }
}

$iterator = new CpuAdjustedDelay(null, 500);

for ($x = 0; $x < 5; $x++) {
    $iterator->add_module(new dummy());
}

while (1) {
    $iterator->iterate();

    for ($x = 0; $x < 1000000; $x++) {
        $z = 2 ^ $x;
    }

    $fp = fopen('/dev/urandom', 'r');
    for ($x = 0; $x < 1000; $x++) {
        fread($fp, 1024);
    }
    fclose($fp);

}
