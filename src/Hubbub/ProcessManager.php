<?php
/*
 * This file is a part of Hubbub, available at:
 * http://github.com/abcarroll/hubbub
 *
 * Copyright (c) 2015, A.B. Carroll <ben@hl9.net>
 * Hubbub is distributed under a BSD-like license.
 *
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code, or available at the URL above.
 */

namespace Hubbub;

/**
 * Class ProcessManager
 * ... defines a routine to execute commands on the local machine and later retrieve them much like a networking classes would behave.  execute() behaving
 * much like the Iterator and pollCompleted() acting much like a socket poll setup.  The difference being no callbacks are called until the process is complete.
 * It is loosely based upon: https://github.com/uuf6429/InterExec
 *
 * @package Hubbub
 */
class ProcessManager {
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var MessageBus
     */
    protected $bus;

    /**
     *
     * @var array
     */
    protected $proc = [];

    /**
     * pipe resources, as [ ['stdin'][$id] => pipe
     * @var array
     */
    protected $pipes = [
        'stdin'  => [],
        'stdout' => [],
        'stderr' => []
    ];

    protected $resultBuffers = [];
    protected $timesStarted = [];
    protected $totalNumberProcessesStarted = 0;

    /**
     * Creates an execution thread to be later retreived using pollCompleted().  The function returns a unique identifier to later correlate the results of the
     * execution with.  If you do not specify an ID, one will be generated for you using a SHA1 hash of the data passedin.
     *
     * @param string      $cmd   The command in which to execute.  This should be a path to an executable + arguments.  It is your responsibility to escape
     *                           shell arguments.
     * @param null|string $id    An ID in which to track the execution by, which will be returned along with the results.  If NULL, one will be generated for
     *                           you using SHA1.
     * @param null|string $cwd   The working directory in which to execute the $cmd with.
     * @param null|array  $env   Environment variables to execute the $cmd with.
     * @param null|array  $other Options to pass to proc_open().  See the documentation for proc_open() for a description of these options.
     *
     * @return string|false The ID of the process thread created, or false on failure.
     * @throws \ErrorException
     */
    public function execute($cmd, $id = null, $cwd = null, $env = null, $other = null) {
        $this->totalNumberProcessesStarted++;

        if($id == null) {
            $id = sha1(microtime() . $cmd . $cwd . $env . $other . mt_rand());
        }

        $pipes = []; // numeric pipes
        $descriptor_spec = [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'r']]; // always static...

        $this->timesStarted[$id] = time();

        /*
         * proc_open() is prepended with exec so that it will not spawn/fork a shell process (e.g. bash)
         * @see http://php.net/manual/en/function.proc-get-status.php#93382
         */
        $this->proc[$id] = @proc_open('exec ' . $cmd, $descriptor_spec, $pipes, $cwd, $env, $other);

        if(!$this->proc[$id]) {
            throw new \ErrorException("Couldn't create process '$cmd' (id=$id)");
        }

        stream_set_blocking($pipes[0], false);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        // pretty names beyond here
        $this->pipes['stdin'][$id] = $pipes[0];
        $this->pipes['stdout'][$id] = $pipes[1];
        $this->pipes['stderr'][$id] = $pipes[2];

        // initialize an empty buffer
        $this->resultBuffers[$id] = '';

        return $id;
    }

    public function pollCompleted() {
        $read = $this->pipes['stdout'] + $this->pipes['stderr'];
        $write = $this->pipes['stdin'];
        $except = null;

        if(count($read) == 0 && count($write) == 0 && count($except) == 0) {
            //echo "No processes to handle... \n";
            return [];
        }

        stream_select($read, $write, $except, 0, 0);
        //echo "select: " . count($read) . " / " . count($write) . " / " . count($except) . "\n";
        foreach($read as $r) {
            $id = array_search($r, $this->pipes['stdout']);
            $data = stream_get_contents($r);
            //echo "$id => " . $data . "\n";
            $this->resultBuffers[$id] .= $data;
        }

        //echo count($write) . " want to write.\n";

        // Cleanup dead processes and return any data we've gathered
        $results = [];
        foreach($this->proc as $id => $proc) {
            $status = proc_get_status($proc);
            if(!$status['running']) {
                //echo "$id exited\n";
                unset($this->proc[$id]);
                unset($this->pipes['stdin'][$id]);
                unset($this->pipes['stdout'][$id]);
                unset($this->pipes['stderr'][$id]);
                proc_close($proc);

                $results[$id] = $this->resultBuffers[$id];
                unset($this->resultBuffers[$id]);
            }
        }

        return $results;
    }
}

