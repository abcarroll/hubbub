<?php

/**
 * Class BncToIrcTranslator translates
 */

namespace Hubbub\IRC\Client;

class BncHandler {
    protected $networkName;

    /**
     * @var \Hubbub\IRC\Client
     */
    protected $irc;

    public function __construct(\Hubbub\IRC\Client $client, $networkName) {
        $this->networkName = $networkName;
        $this->irc = $client;
    }

    public function onBusRecv($msg) {
        if($msg['protocol'] == 'irc' && $msg['network'] == $this->networkName) {

        }
    }

    public function onBusSend($msg) {
        return $msg;
    }

    public function on_connect() {

    }

    public function on_disconnect() {
    }

    public function on_join($line) {
        foreach($line->join['channels'] as $c) {
            $this->irc->bPublish([
                'action'  => 'join',
                'channel' => $c . '.' . $this->networkName
            ]);
        }
    }

    public function on_privmsg($line) {
        var_dump($line);


        $this->irc->bPublish([
            'action'  => 'privmsg',
            'to'      => $line->privmsg->to,
            'from'    => $line->from,
            'message' => $line->privmsg->msg
        ]);
    }

    public function on_recv($data) {
        // do nothing...
    }

    public function on_send($data) {
        return $data;
    }

}