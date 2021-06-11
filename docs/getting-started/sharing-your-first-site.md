---
title: Share your first site
order: 2
---

# Share your first site

Once you have installed Expose, you are ready to go and share your first local site.

The easiest way to share your local sites is by going into the folder that you want to share and run the `expose` command from your command line:

```bash
cd ~/Sites/my-awesome-project/

expose
```

This command uses your Expose network token and connects to the Expose network server at sharedwithexpose.com and creates a tunnel. If you don't have an Expose token and don't want to host your own server, you can create a free account and get a token [here](https://beyondco.de/login). 

To learn more about sharing your local sites, check out the [sharing local sites](/docs/expose/client/sharing) documentation.

## Using the Expose network at sharedwithexpose.com

Expose is the only open source tunnel service that is written in PHP. This means that you can host your own server and this on its own makes it a fantastic alternative to ngrok.

Before you install your own server, you can try Expose with the free plan of the Expose network and see if your like the features for PHP developers. To access the Expose network, you need an Expose token.

You can obtain a token by signing in to your [Beyond Code account](https://beyondco.de/login). If you don't have an account, you can [sign up and create an account](https://beyondco.de/register) for free.

## Authenticating with sharedwithexpose.com

To register and use the given credentials, just run the following command:

```bash
expose token [YOUR-AUTH-TOKEN]
```

This will register the token globally in your expose configuration file and all following Expose calls will automatically use the token to authenticate with the network. In case that you have access to a team on an Expose Pro plan, you can use this command to switch to the team and get access to the reserved subdomains or white label domains. 
