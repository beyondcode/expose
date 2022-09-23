---
title: Share your first site
order: 2
---

# Share your first site

Once your authentication token is setup, you are ready to share your first site with Expose.
Expose creates a tunnel between your local development URLs/HTTP server and a publicly available web server.

The easiest way to share your local URLs is by calling `expose share` followed by the local URL that you want to share:

```bash
# Will share access to http://192.168.2.100
expose share http://localhost:3000

# Will share access to http://my-local-site.dev
expose share my-local-site.dev
```

By default, Expose assumes that you want to share unenecrypted local traffic through HTTP. If you want to share a local HTTPS URL prepend the protocol to the url, like this:

```bash
# Will share access to https://my-local-site.dev 
# Note the https for tunneling locally encrypted sites
expose share https://my-local-site.dev
```

## Custom Subdomains ::pro

To make your life easier, Expose tries to share your local URLs using custom subdomains. This allows you to share your local URL `my-local-site.dev` as `my-local-site.us-1.sharedwithexpose.com`.

By default, Expose uses a slugified version of the URL that you want to share, but you can also [choose your own custom subdomain](/docs/client/sharing#share-a-local-site-with-a-given-subdomain).
