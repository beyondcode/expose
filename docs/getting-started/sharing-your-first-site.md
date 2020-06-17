---
title: Share your first site
order: 2
---

# Share your first site

Once you have installed Expose, you are ready to go and share your local sites.

The easiest way to share your local sites is by going into the folder that you want to share and run `expose`:

```bash
cd ~/Sites/my-awesome-project/

expose
```

This will connect to the provided server at expose.dev and give you a tunnel that you can immediately start using.

To learn more about how you can share your local sites, check out the [sharing local sites](/docs/expose/client/sharing) documentation.

## Using the provided server at expose.dev

A big advantage of Expose over other alternatives such as ngrok, is the ability to host your own server. To make sharing your sites as easy as possible, we provide and host a custom expose server on our own - so getting started with expose is a breeze.

This server is available free of charge for everyone, but makes use of Expose's authentication token authorization method.

Therefore, in order to share your sites for the first time, you will need an authorization token.

You can obtain such a token by singing in to your [Beyond Code account](https://beyondco.de/login). If you do not yet have an account, you can [sign up and create an account](https://beyondco.de/register) for free.

## Authenticating with sharedwithexpose.com

To register and use the given credentials, just run the following command:

```bash
expose token [YOUR-AUTH-TOKEN]
```

This will register the token globally in your expose configuration file, and all following expose calls will automatically use the token to authenticate with the server.
