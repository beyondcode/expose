---
title: Installation
order: 1
---

# Installation
 
Expose is a PHP package and you can install the client for your local machine as a global composer dependency:

```bash
composer global require beyondcode/expose
```

After that, you are ready to go and can [share your first site](/docs/expose/getting-started/sharing-your-first-site).

### Extending Expose

By default, Expose comes as an executable PHAR file. This allows you to use all Expose features out of the box – without any additional setup required.

If you want to modify Expose and want to add custom request/response modifiers, you need to clone the GitHub repository instead of the global composer dependency.

You can learn more about the customization of Expose in the [extending Expose](/docs/expose/extending-the-server/subdomain-generator) documentation section.
