---
title: Settings
order: 2
---

# Settings

Expose provides two API endpoints that allow you to either read or update the currently active server settings.

## Reading the settings

To retrieve the currently active configuration, you can perform a GET request to the `/api/settings` endpoint:

The result looks like this:

```json
{
   "configuration":{
      "hostname": "expose.dev",
      "port": 8080,
      "database": "/home/forge/expose/database/expose.db",
      "validate_auth_tokens": false,
      "maximum_connection_length": 0,
      "subdomain": "expose",
      "subdomain_generator": "App\\Server\\SubdomainGenerator\\RandomSubdomainGenerator",
      "users": {
         "username":"password"
      },
      "user_repository": "App\\Server\\UserRepository\\DatabaseUserRepository",
      "messages": {
         "message_of_the_day":"Thank you for using expose.",
         "invalid_auth_token":"Authentication failed. Please check your authentication token and try again.",
         "subdomain_taken":"The chosen subdomain :subdomain is already taken. Please choose a different subdomain."
      }
   }
}
```

## Updating the settings

To update the currently active settings, send a POST requests to the `/api/settings` endpoint.

The endpoint expects you to send the following data:

```
validate_auth_tokens: BOOLEAN

maximum_connection_length: INTEGER

messages: ARRAY
messages.message_of_the_day: STRING
messages.invalid_auth_token: STRING
messages.subdomain_taken: STRING
```

You will receive a response containing the updated configuration as JSON.