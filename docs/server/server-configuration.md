---
title: Server Configuration
order: 5
---

# Server Configuration

Within the Expose admin interface, you can configure how you want your specific expose server to behave.

Here are the available settings:

![](/img/expose_settings.png)

## Authentication

When you start your expose server, anyone is able to connect to it by default. If you want to restrict your server only to users that have a valid "authentication token", you can simply check the checkbox. Only registered users / authentication tokens are then able to connect to your server.

> **Note:** This is only a temporary modification for as long as your expose server is running. To permanently enable this feature, modify your expose config file.

## Message of the day

This message will be shown when a sucessful connection to the expose server can be established. You can change it on demand, or modify it permanently in your expose configuration file.

## Maximum connection length

You can define how long you want your users connection to your expose server to be open at maximum. This time can be configured in minutes. Once the connection exceeds the specified duration, the client connection gets closed automatically.

## Authentication failed

This message will be shown when a user tries to connect with an invalid authentication token. If your users can somehow obtain an authentication token, this is a great place to let them know how to do it.

## Subdomain taken

This message will be shown when a user tries to connect with an already registered subdomain. This could be any user-registered subdomain, as well as the expose admin dashboard subdomain.