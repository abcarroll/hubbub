<?php

/*
 * (C) Unknown :(
 * Not my code - ABC3
 */

require_once('php-sip-read-only/PhpSIP.class.php');

try
{
  $api = new PhpSIP('10.0.77.1'); // IP we will bind to
  $api->setMethod('MESSAGE');
  $api->setFrom('sip:REMOVED@sip.skype.com');
  $api->setUri('sip:REMOVED@sip.skype.com');
  $api->setBody('Hi, can we meet at 5pm today?');
  $res = $api->send();
  echo "res1: $res\n";
  
} catch (Exception $e) {
  
  echo $e->getMessage()."\n";
}
