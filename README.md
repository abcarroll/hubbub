# Hubbub: Messaging Hub #
Hubbub is under heavy development.  Until we have more working software, I doubt you will find much information about it.

## The Idea ##
In a nutshell, Hubbub is meant to be a Proxy/BNC for all major IM protocols, including IRC.  Server and client components are independent, meaning you can theoretically create a translation gateway (X Network over Y Protocol) very easily, by only implementing the client or server component that Hubbub is missing.

My philosophy is that instant messaging and communication is a core component of the internet.  I want to be able to harness all possible protocols and networks, in one place, and make it so other people can extend, automate, and use these protocols with ease. Especially with a simple, easy, well known language such as PHP (and, you could extend it to other languages easily as well.)  To be able to save and search chat logs, setup auto responses, and have 24/7 online presence.  In addition, not restricting a protocol to a specific arena – for example, there is no FooProtocol client on XYZ device?  That's OK if there is a BarProtocol client, we can make a gateway.

## Coding Standards ##
  * Just take a look at the code.  If you submit code, please try to phpDoc it.  A lot of the code isn't phpDoc'd, so it's not a requirement.
  * If you submit code, I don't care if it's spaces or tabs, or badly formatted, because if you have a decent editor, it's a 1 second fix to convert back to tabs.  If you hate my formatting, and have a decent editor, it's a one second fix to change it to your preferred style.
  * I do not use camel case for methods.  I originally tried to adhere to php-fig standards but some are very arbitrary and some ridiculous (but, some good).

## History ##
Hubbub is based on some code I wrote over 10 years ago when I was about 14 years old, called mpiBot.  You can still find the original project in all of it's glory, as well as a Wiki I setup about a year ago in attempts to revive the project, over at http://mpibot.sf.net.  mpiBot was meant to only be an IRC bot and BNC.  Hubbub is meant to be much more.

## Status ##
Hubbub isn't ready yet.  There is a mostly full featured IRC client, and partial IRC server (BNC-side) component.  There used to be interest in Skype however Skype stopped supporting sending messages through it's dbus API in Dec 2013.  If there is ever a way to accomplish this withotu legal ramifications, it will be so.  Right now, the main component missing is a reliable messaging hub to aid in moving chat messages around internally in a protocol-neutral way, and a configuration interface.  There is a lot of loose ends to be tied up yet.

I am *slowly* making progress.  This project is intended to be a very well laid out example of Object Oriented Programming with dependency injection & IoC techniques which are relatively new concepts to me.

## TODO ##
  * ~~Modernize Code: Most of this code was written before true namespace support so you see PEAR-like class names.~~
  * Finish the messaging bus, merge the two bus classes.  Possibly refactor into MessageBus not MsgBus.
  * Write out all classes & namespaces.  Refactor into new namespaces and write Interfaces and Abstract classes where needed.
    * Make sure the getters and setters are consistent.
  * Refactor the IRC client considerably.  Get the modular bits working again.  Refactor to use proper camelCase callback methods.
    Things such as channels, commands, and nicks should be objects.
  * Move networking objects into DI injection on the Bnc/IRC Objects
     - Fix client disconnection issue
  * The bootstrapping is still a little over-done.  For the most part, it should work, but I believe the bootstrap and conf class needs to be refactored
    into more than just those two classes.
  * *Re-implement chroot() capabilities from mpiBot*
  * Write basic configuration script as a demo.  Should ask:
    * Logging options (to file, context dumps)
    * Auto-detect or ask which Net class to use (socket, stream, fsockopen)
    * Run BNC? Listening options, password
    * Global IRC Options: Default nick/user/realname, ctcp & dcc options, global irc modules
    * Add networks
      * Per-server options: nick/user/realname, ctcp&dcc options, server modules
        * Auto join, SASL, TLS, auto-login for services
      * Add servers
    * { .. repeat until satisfied }
  * *Clean up circular reference issue.* (Somewhat done)
  * Move Conf into a JSON loader.  Write a demo mySQL conf class.
  * Ensure all headers are up-to-date
  * Ensure all phpDocs are up-to-date.
  * Ensure all throttlers have a consistent API.
  * Attempt to complete the CpuAdjustedDelay Throttler.
  * Refactor code to make better use of Built-in Spl Classes such as ArrayObject, or the SplObserver/SplSubject for the
    message bus (or is our's too different from that specific implementation?)

## License ##
Hubbub is available under a BSD-like license.  Please see LICENSE.txt for full license text.  Some files do not have a copyright header, those files are still subject to my copyright, unless specifically  noted with a separate copyright header.

While you are not legally required to do so, I kindly ask if you make commercial use of my software, even as a service, you just simply link back to the SourceForge homepage, at http://hubbub.sf.net



