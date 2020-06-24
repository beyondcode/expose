---
title: SSL Support
order: 2
---

# SSL Support

Once your Expose server is running, you can only access it over the port that you configure when the server gets started.

If you want to enable SSL support, you will need to use a proxy service - like Nginx, HAProxy or Caddy - to handle the SSL configurations and proxy all non-SSL requests to your expose server.

A basic Nginx configuration would look like this, but you might want to tweak the SSL parameters to your liking.

```
server {
  listen        443 ssl;
  listen        [::]:443 ssl;
  server_name   expose.yourapp.tld;

  # Start the SSL configurations
  ssl                  on;
  ssl_certificate      /etc/letsencrypt/live/expose.yourapp.tld/fullchain.pem;
  ssl_certificate_key  /etc/letsencrypt/live/expose.yourapp.tld/privkey.pem;

  location / {
    proxy_pass             http://127.0.0.1:8080;
    proxy_read_timeout     60;
    proxy_connect_timeout  60;
    proxy_redirect         off;

    # Allow the use of websockets
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection 'upgrade';
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto https;
    proxy_set_header Host $host;
    proxy_cache_bypass $http_upgrade;
  }
}
```
