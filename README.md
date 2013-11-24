# Hubbub: Messaging Hub #
Hubbub is under heavy development.  Until we have more working software, I doubt you will find much information about it.

## The Idea ##
In a nutshell, Hubbub is meant to be a Proxy/BNC for all major IM protocols, including IRC.  Server and client components are independent, meaning you can theoretically create a translation gateway (X Network over Y Protocol) very easily, by only implementing the client or server component that Hubbub is missing.

My philosophy is that instant messaging and communication is a core component of the internet.  I want to be able to harness all posssible protocols and networks, in one place, and make it so other people can extend, automate, and use these protocols with ease. Especially with a simple, easy, well known language such as PHP (and, you could extend it to other languages easily as well.)  To be able to save and search chat logs, setup auto responses, and have 24/7 online presence.  In addition, not restricting a protocol to a specific arena – for example, there is no FooProtocol client on XYZ device?  That's OK if there is a BarProtocol client, we can make a gateway.

## Coding Standards ##
  * I use tabs.  You are free to change my tabs to spaces with any decent editor.
  * If you submit code, I don't care if it's spaces, because if you have a decent editor, it's a 1 second fix to convert back to tabs.  If you hate my tabs, and have a decent editor, it's a one second fix to change it to spaces.
  * I do not use camel case for methods.  I originally tried to adhere to php-fig standards but some are very arbitrary and some ridiculous (but, some good).

## Status ##
Hubbub isn't ready yet.  There is a mostly full featured IRC client, and partial IRC server (BNC-side) component.  There is also a Skype example, however Skype is removing desktop in December of 2013.  After Dec 2013, I do not know how long the binaries with Desktop API support will still function.  Right now, the main component missing is a reliable messaging hub to aid in moving chat messages around internally in a protocol-neutral way.  And a configuration interface.

## License ##
Hubbub is available under a BSD-like license.  Please see LICENSE.txt for full license text.

While you are not legally required to do so, I kindly ask if you make commercial use of my software, even as a service, you just simply link back to the SourceForge homepage, at http://hubbub.sf.net
