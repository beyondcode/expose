---
title: Custom Views
order: 3
---

# Custom Views

## Homepage

The expose server allows you to modify the "homepage" of your server. This is the website that will be shown to your users when you access your server without any subdomain.

To customize it, clone the repository and modify the file located at `resources/views/homepage.twig`.

## 404 - Tunnel not found

Just like the homepage, you can also modify 404 views in case someone tries to access a subdomain that is not registered on the expose server.

To customize the error view, clone the repository and modify the file located at `resources/views/errors/404.twig`.
