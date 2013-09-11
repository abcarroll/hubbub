<?php
 /*
  * This file is a part of Hubbub, freely available at http://hubbub.sf.net
  *
  * Copyright (c) 2013, Armond B. Carroll <ben@hl9.net>
  * For full license terms, please view the LICENSE.txt file that was
  * distributed with this source code.
  */

	// See http://php.net/errorfunc.configuration.php#ini.error-reporting
	error_reporting(2147483647);

	// TODO Replace with SPL..
	function autoload_hubbub($class) { 
		require 'lib/core/' . $class . '.php';
	}

	spl_autoload_register('autoload_hubbub');
	spl_autoload_register(); // Register php-fig SPL Style autoloading

	/// Check if we're running a web instance
	if(php_sapi_name() != 'cli') {
		header('Content-Type: text/plain');
		ob_implicit_flush();
		echo "I think I am running in a web environment.  I normally need to be run in a shell.  I will continue anyway, but please be advised this might be a bad idea.\n";
	}

	// Everything.
	$h = new hubbub();
	$h->main();
