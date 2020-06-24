---
title: Sharing your local sites
order: 1
---

# Sharing local sites

Expose allows you to share any kind of HTTP/HTTPS traffic for websites that you can reach on your own computer, with anyone on the internet.

There are multiple different ways on how you can initiate the site sharing with Expose. 

## Sharing the current working directory

To share the current working directory with expose, all you need to do is go into the directory and call `expose`.

This makes the assumption that you have access to the current working directory name as a domain with the `.test` TLD.  

If you are using Laravel Valet, the configured Valet subdomain will automatically be detected.

If you are using a different domain for your local sites, you can change the default TLD that expose uses in the [configuration file](/docs/expose/client/configuration).

For example: 

```bash
# Will share a local site "my-site.test" as "my-site.EXPOSE-SERVER"
~/Sites/my-site/ expose

# Will share a local site "api.my-site.test" as "api-my-site.EXPOSE-SERVER"
~/Sites/api.my-site/ expose
```

## Sharing a local site explicitly

If you want to explicitly share a local URL, without going into a specific folder first, you can do this by using the `expose share` command.

> By default, this command will generate a unique subdomain for the shared URL.

```bash
# Will share access to http://192.168.2.100 using a randomly generated subdomain
expose share http://192.168.2.100

# Will share access to http://my-local-site.dev using a randomly generated subdomain
expose share my-local-site.dev

# Will share access to https://my-local-site.dev using a randomly generated subdomain (note the https)
expose share https://my-local-site.dev
```

## Share a local site with a given subdomain

You can also share one of your local sites explicitly and specify which exact subdomain you want Expose to use when sharing the site.  
This works similar to the paid offerings of Ngrok - but you can use it with your own custom server.

To specify the subdomain, pass the `--subdomain` option to expose:

```bash
expose share my-site.test --subdomain=my-site
```

If the chosen subdomain is already taken on the Expose server, you will see an error message and the connection to the Expose server gets closed.
