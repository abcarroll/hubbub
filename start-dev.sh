#!/bin/bash

# Clear previous logs (or comment this out)
> log/parsed.log;
> log/raw-protocol.log;
> log/console.log;
> log/stdout.log;
> log>stderr.log;

# Start Hubbub and tee stdout/stderr to log files
./start.php > >(tee log/stdout.log) 2> >(tee -a stderr.log >&2)
