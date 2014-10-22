<?php
    /*
     * This file is a part of Hubbub, freely available at http://hubbub.sf.net
     *
     * Copyright (c) 2013, Armond B. Carroll <ben@hl9.net>
     * For full license terms, please view the LICENSE.txt file that was
     * distributed with this source code.
     */

    /*
        This is a working example of Skype API Usage. It requires the dbus
        pecl extension.  On my personal ubuntu machine, installation steps
        were, (as root):

            apt-get install pkg-config libdbus-1-dev libxml2-dev
              pecl install channel://pecl.php.net/dbus-0.1.1
              echo 'extension=dbus.so' > /etc/php5/conf.d/99-dbus.ini

          You may need more packages - some may have already been installed
          as dependencies of other random software.  Google is helpful.
          I got this working on a remote "headless" machine by installing
          tightvnc and then export DISPLAY=:1 ... There is a tutorial here:

          Links/Reference:
              http://pecl.php.net/package/DBus
              http://www.immortal-rs.com/wp-content/uploads/2013/03/SkypeSDK.pdf
              https://www.digitalocean.com/community/articles/how-to-setup-vnc-for-ubuntu-12

          Scroll down and configure the two instances of $test_user.  Make sure you are logged
          in to Skype.  The first run, it will block the script.
      */

    class SkypeDbusConnector {
        private $dbus, $proxy;

        function __construct() {
            // TODO Better Error Handling
            if(!class_exists('Dbus')) {
                trigger_error("The Dbus PECL Extension is not installed, sorry.", E_USER_WARNING);

                return false;
            } else {
                $this->dbus = new Dbus(Dbus::BUS_SESSION, true);
                $this->dbus->registerObject('/com/Skype/Client', 'com.Skype.API.Client', 'SkypeDbusConnector');
                $this->proxy = $this->dbus->createProxy("com.Skype.API", "/com/Skype", "com.Skype.API");
            }
        }

        public function send($cmd) {
            return $this->proxy->Invoke($cmd);
        }

        public function poll() {
            // Seems the parameter of waitLoop() is time to block/wait in ms
            return $this->dbus->waitLoop(1);
        }

        static function notify($a) {
            var_dump($a);
            #@list($a, $b, $c, $d) = explode(' ', $a, 4);
        }
    }

    class SkypeAPI {
        private $c;
        private $local_protocol = 7;
        private $remote_protocol = -1;

        private $error_codes = [
            -1 => 'UNEXPECTED_PROTOCOL_DATA',
            68 => 'ACCESS_DENIED',
        ];

        public function __construct($connector) {
            $this->c = $connector;
        }

        public function send($cmd) {
            return $this->c->send($cmd);
        }

        private function handle_error($e) {
            list($error, $number) = explode(' ', $e);
            if(isset($this->error_codes[$number])) {
                $friendly = $this->error_codes[$number];
            } else {
                $friendly = '';
            }

            echo "SkypeAPI Error: $friendly ($e)\n";

            return false;
        }

        public function setup($name) {
            $r = $this->send("NAME $name");

            if($r == 'OK') {
                $r = $this->send('PROTOCOL ' . $this->local_protocol);
                if($r != "PROTOCOL " . $this->local_protocol) {
                    trigger_error("SkypeAPI Returned '$r', which isn't the protocol I was designed to use (v" . $this->local_protocol . ")", E_USER_WARNING);
                }

                if(substr($r, 0, 9) == 'PROTOCOL ') {
                    list(, $protocol) = explode(' ', $r);
                    $this->remote_protocol = $protocol;
                } else {
                    trigger_error("SkypeAPI Unexpected protocol data '$r', was expecting a PROTOCOL message", E_USER_WARNING);
                }
            } elseif(substr($r, 0, 6) == 'ERROR ') {
                return $this->handle_error($r);
            } else {
                trigger_error("SkypeAPI Unexpected protocol data '$r', was expecting a OK or ERROR message", E_USER_WARNING);
            }
        }

        public function poll() {
            return $this->c->poll();
        }

    }


    $s = new SkypeAPI(new SkypeDbusConnector());
    $s->setup("PHPTestApp2");

    // Send two messages to two separate people...

    $test_user = '';
    $chat = $s->send("CHAT CREATE $test_user");
    list(, $id, ,) = explode(" ", $chat);
    var_dump($s->send("OPEN CHAT $id"));
    var_dump($s->send("CHATMESSAGE $id This is a test message sent over PHP :)"));

    $test_user = '';
    $chat = $s->send("CHAT CREATE $test_user");
    list(, $id, ,) = explode(" ", $chat);
    var_dump($s->send("OPEN CHAT $id"));
    var_dump($s->send("CHATMESSAGE $id This is a test message sent over PHP :)"));

    while (1) {
        echo "Polling...\n";
        $p = $s->poll();
        usleep(1000000);
    }

    echo "\n\n";