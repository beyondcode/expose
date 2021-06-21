---
title: Sharing TCP ports
order: 3
---

# Sharing TCP Ports ::pro

Expose not only allows you to share your local development URLs, but you can also use Expose to share any of your local TCP ports.

For example, you might want to share your local copy of [HELO](https://usehelo.com) so that your PHP application can send test emails right through your local SMTP server.

You can share a local TCP port by calling `share-port` followed by the local port number that you want to share:

```bash
expose share-port 2525
```

The Expose server will assign a random TCP port that you can then use on other servers and services to connect to the tunnel.