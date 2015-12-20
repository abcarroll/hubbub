# Hubbub: Universal Chat System

## The idea, history

mpiBot was originally meant to be a modular IRC bot.  I wrote mpiBot when I was 14 years old, over 10 years ago.  Hubbub is a fork of mpiBot's ideas
and in fact still uses some of the same code for the IRC protocol.  All projects were also meant as programming exercises, and to push the PHP language to
it's limits.

My philosophy is that instant messaging and communication is a core component of the internet.  I want to be able to harness all possible protocols and 
networks, in one place, and make it so other people can extend, automate, and use these protocols with ease. Especially with a simple, easy, well known 
language such as PHP (and, you could extend it to other languages easily as well.)  To be able to save and search chat logs, setup auto responses, and 
have 24/7 online presence. 

In addition, by using the dead-simple messaging Bus, we can easily create protocol bridges.  A concrete example: If a device/platform such as Android does
not have a client for Tox, but does have a client for IRC, we can connect to our Hubbub instance via IRC and use it to send and receive messages on Tox.

## Current Status
Currently, Hubbub after several years of development is *almost* usable as an IRC client and BNC.  It has been written with very modernized coding techniques,
and meticulously planned, and re-written several times to ensure a stable foundation to grow upon.

## Plans for the Future
Ultimately, Hubbub will hopefully one day support dozens of protocols.  Among them that I would love to see: XMPP, SIP, Tox, Mumble, TextSecure, Twitter (API), 
Reddit (API), and proprietary protocols such as Steam, OSCAR, YMSG, and MSNP.  

Skype support will likely never happen, unfortunately.  Microsoft has demonstrated they will do everything within their legal capabilities to bar anyone
else from creating a competing client.  A few years ago, Microsoft invoked DMCA and killed a project to reverse engineer the protocol directly.  Using something
such as Skype4Py is a possibility. However it is my understanding that Skype/Microsoft is also actively targeting these projects by removing the DBus interface,
making it all but impossible to interface with Skype.  My best advice: Do not use Skype.

In addition to writing protocol support, a high priority for the project is to write a universal web client, so that ultimately one could use your web browser
to chat over any protocol that Hubbub supports.  These would be local installations, not SaaS.  The goal/idea being that you could install Hubbub on a cheap
VPS, and be able to log-in to your Hubbub instance anywhere in the world on any device that has a quasi-modern web browser.

## Architecture
Hubbub is meant to be totally self-contained aside from modules you may elect to install.  It uses a built-in copy of "Dice", by Tom Butler for dependency 
injection.  Dice is distributed under the BSD license.  Some extras from the git repository were removed and only the minimum necessary files are left.

Hubbub has a few main components:

 - Start / Bootstrap Routine: start.php and the autoloader.  Hubbub uses PSR-4 compatible autoloading with the namespace root under 'src/'.
 - \Net objects are networking objects: Two types of networking exist, 'Stream' and 'Socket' which correspond to stream_* and socket_* functions in PHP.  
 - \Protocol objects are a few thin interfaces in which protocol objects (eg. IRC\Client) implement
 - Protocol objects are event handlers, Networking objects are event-producers.  The system in which this works uses a poor OO pattern, admittedly.  See setProtocol().
 - Aside, there is a configuration object, a logger object (PSR-3 compatible), an iterator (poor name for it), and messaging bus which is a subscribe/publish pattern.
 - The messaging bus is meant to make protocol-agnostic messaging possible between unlike chat protocols.  Also, to extend the system by subscribing to relevant
 events.  For example, you could subscribe to all private messages across all protocols, and write a module that responds to commands that start with '@' without 
 worrying about the underlying protocol.
 
## Getting help
Hubbub has an official IRC channel on Freenode, at `#hubbub`.  You are welcome to join, and idle.  You can test your Hubbub in `#hubbub-test`.  We'd love to see your bot/bnc.

## Credits

### Authors

> Hubbub is written and maintained by:
 - A.B. Carroll <ben@hl9.net>

### Contributors

> Creative Support, Technical Information, Code Review Provided by:
 - Rob DeHart <rob@1606inc.com>
 - Mike Preston <mike@technomonk.com>

> Additionally, some low level protocol formats, numeric and mode tables  were created with the help of data from
> Simon Butcher pickle@alien.net.au

> Some helpful insights into proper Dependency Injection patterns provided by regulars of Freenode / ##php channel.

> Hubbub uses DICE 2.0, which is (C) Copyright 2012-2015 Tom Butler <tom@r.je> | https://r.je/dice.html and distirbuted under the BSD license.  A built in
version of Dice is distributed with Hubbub in 'src/Dice/'.

## License ##
Hubbub is available under a BSD-like license.  Please see LICENSE.txt for full license text.  Some files do not have a copyright header, those files are still
subject to my copyright, unless specifically noted with a separate copyright header.

While you are not legally required to do so, I kindly ask if you make commercial use of my software, even as a service, you just simply link back to the official
GitHub project page, http://github.com/abcarroll/hubbub

