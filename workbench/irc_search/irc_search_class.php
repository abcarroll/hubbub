<?php

/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2015, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */

/* class file_manager extends SplFileObject {
    function __construct($observer, $filename, $open_mode = 'r', bool $use_include_path = false, resource $context) {
        parent::__construct($observer->file_prefix . '/' . md5($filename), $open_mode, $use_include_path, $context);
    }
}*/


class file_cache {
    private $prefix = './var/file-cache/';
    private $index_file;
    private $index = [];

    public function __construct() {
        $this->index_file = $this->prefix . '/index.dat';
        if(is_readable($this->index_file)) {
            $index = file($this->index_file);
            foreach ($this->index as $entry) {
                $split = explode("\0", $entry);
                $this->index[$split[0]] = $split[1];
            }
        } else {
            $this->save_index();
        }
    }

    /* TODO Needs proper error handling */
    private function save_index() {
        $file = new SplFileObject($this->index_file, 'w');
        foreach ($this->index as $key => $val) {
            $file->fwrite($key . "\0" . $val . "\n");
        }
        $file->close();

        return true;
    }

    private function open($name, $mode, $expire = 0) {
        $this->index[$name] = $expire;
        $this->save_index();

        return new SplFileObject($this->prefix . '/' . md5($filename), 'a+');
    }
}


class irc_search {
    private $netsplitde_url = 'http://irc.netsplit.de/networks/';
    private $malirc_url = 'http://www.mirc.com/servers.ini';

    function __construct() {
        $this->parse_netsplitde_networks(file_get_contents("var/netsplitde"));
    }

    function parse_netsplitde_networks($data) {
        preg_match('/<table class="netlist" width="100%">(.*)<\/table>/ism', $data, $table_match, false);
        preg_match_all('/<a.*class="(?:competitor|applicant|maverick)".*>(.*)<\/a>/ismU', $table_match[1], $network_names);
        print_r($network_names);
    }
}

$irc_search = new irc_search();
			
