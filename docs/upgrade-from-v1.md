---
title: Upgrade from Expose v1 
order: 2
---

# Upgrade Guide

Expose 2 is now available and comes with a lot of new features and improvements! We worked very hard to make the transition from Expose 1.x to 2.x as smooth as possible.

The easiest way to update your existing Expose 1 installation to Expose 2, is by using composer.

```bash
composer global require beyondcode/expose:2.0
```

This will download and install the latest version of Expose. Your existing authentication token and configuration file will still be valid after updating to the latest version.

**Important:** Even though the Expose 1.x client is backwards compatible with the new Expose servers, please upgrade to the latest client as soon as possible, as we will fade out support for the Expose 1.x client in the future.

## Upgrading to Expose ::pro

Some Expose features are not available on our free server and require self-hosting or a Pro plan. These features include custom subdomains, or the newly added sharing of TCP ports. 

If you want to upgrade your existing setup to Expose Pro, please [create a new Expose account](/register) and you get a new authentication token that can upgrade to Expose Pro.
