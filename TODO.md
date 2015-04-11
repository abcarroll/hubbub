# To Do #

This is a short term to-do list.

-----------------------------------
PHASE 1
-----------------------------------
Take each modular component, isolate it, and rebuild it as an isolated
module.

  - Networking (all variants)
  - IRC Client
  - IRC Server
  - XMPP Client


-----------------------------------
PHASE 2
-----------------------------------
- Clean up circular reference issue.
- Move Conf into a JSON loader.  Write a demo mySQL conf class.
- Finish the messaging bus, merge the two bus classes.
- Move networking objects into DI injection on the Bnc/IRC Objects
    - Fix client disconnection issue
- Ensure all headers are up-to-date
- Ensure all phpDocs are up-to-date.
- Ensure all throttlers have a consistent API.
- Attempt to complete the CpuAdjustedDelay Throttler.
- Refactor code to make better use of Built-in Spl Classes such as ArrayObject, or the SplObserver/SplSubject for the
    message bus (or is our's too different from that specific implementation?)