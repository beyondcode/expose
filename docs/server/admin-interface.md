---
title: Admin Interface
order: 3
---

# Admin Interface

The Expose server comes with a beautiful admin interface, that makes configuring your server a breeze.

The admin interface is available at a specific subdomain on your expose server. By default it is called "expose", but you can change this in the configuration file:

```
...

/*
|--------------------------------------------------------------------------
| Subdomain
|--------------------------------------------------------------------------
|
| This is the subdomain that your expose admin dashboard will be available at.
| The given subdomain will be reserved, so no other tunnel connection can
| request this subdomain for their own connection.
|
*/
'subdomain' => 'expose',

...
```

So you can reach the admin interface at http://expose.your-domaion.com.

## Authentication

Since the expose admin interface allows you to change and modify your expose server configuration at runtime, access to the admin interface is protected using basic authentication.
You can define which user/password combinations are allowed in the configuration file:

> **Note:** You will need to restart your expose server, once you change this setting in order for the changes to take effect.

```
...

/*
|--------------------------------------------------------------------------
| Users
|--------------------------------------------------------------------------
|
| The admin dashboard of expose is protected via HTTP basic authentication
| Here you may add the user/password combinations that you want to
| accept as valid logins for the dashboard.
|
*/
'users' => [
    'username' => 'password'
],

...
```

### Users

![](/img/expose_users.png)

Here you can list, add and delete all users that you want to be able to connect to your expose server. 
The users will be stored in a SQLite database that can be modified in the expose configuration file.

You only need to add users to your expose server, if you have the auth token validation enabled.

### Shared sites

![](/img/expose_admin.png)

Once you and others start sharing their local sites with your server, you can see a list of all connectes sites here.
You can see the original client host that was shared, the subdomain that was associated to this and the time and date the site was shared.

The expose server can also disconnect a site from the server. Just press on the "Disconnect" button and the client connection will be closed.

### Settings

![](/img/expose_settings.png)

Here you can see and modify your Expose server settings.