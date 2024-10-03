---
title: Advanced Reference
order: 8
---

## Detecting Expose as a Proxy

When Expose serves your local site it automatically sets the standard `HTTP_X_FORWARDED_HOST` header 
which contains the real domain being proxied.

If your application needs to determine whether Expose is the active Proxy server in order to 
take certain specific actions, you can check for the presence of either of these headers:

 - `HTTP_X_EXPOSED_BY` contains the name of the Expose proxy server
 - `HTTP_X_EXPOSE_REQUEST_ID` contains a unique ID

