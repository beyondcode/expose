---
title: Installation
order: 1
---

# Installation

## As a global composer dependency

Expose can be installed using composer.  
The easiest way to install expose is by making it a global composer dependency:

```bash
composer global require beyondcode/expose
```

## As a docker container

Expose has a `Dockerfile` already in the source root.
You can build and use it without requiring any extra effort.

```bash
docker build -t expose .
```

Usage:

```bash
docker run expose <expose command>
```

Examples:

```bash
docker run expose share http://192.168.2.100 # share a local site
docker run expose serve my-domain.com # start a server
```

Now you're ready to go and can [share your first site](/docs/expose/getting-started/sharing-your-first-site).

### Extending Expose

By default, expose comes as an executable PHAR file. This allows you to use all of the expose features, like sharing your local sites, out of the box - without any additional setup required.

If you want to modify expose, for example by adding custom request/response modifiers, you will need to clone the GitHub repository instead.

You can learn more about how to customize expose in the [extending Expose](/docs/expose/extending-the-server/subdomain-generator) documentation section.
