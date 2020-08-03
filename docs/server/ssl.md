---
title: SSL Support
order: 2
---

# SSL Support

Once your Expose server is running, you can only access it over the port that you configure when the server gets started.

If you want to enable SSL support, you will need to use a proxy service - like Nginx, HAProxy, Apache2 or Caddy - to handle the SSL configurations and proxy all non-SSL requests to your expose server.

## Nginx configuration

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

## Apache2 configuration

A basic Apache configuration would look like this, but you might want to tweak the SSL parameters to your liking.

```
Listen 80
Listen 443

<IfModule mod_ssl.c>
<VirtualHost *:443>
	ServerName expose.domain.tld
	ServerAlias *.expose.domain.tld
	LoadModule        proxy_module            modules/mod_proxy.so
	LoadModule        proxy_http_module       modules/mod_proxy_http.so

	ServerAdmin admin@domain.tld

	ProxyPass "/" "http://localhost:8080/"
	ProxyPassReverse "/" "http://localhost:8080/"
	ProxyPreserveHost On
	

	# Needed for websocket support
	RewriteCond %{HTTP:UPGRADE} ^WebSocket$ [NC,OR]
	RewriteCond %{HTTP:CONNECTION} ^Upgrade$ [NC]
	RewriteRule .* ws://127.0.0.1:8080%{REQUEST_URI} [P,QSA,L]

	<Proxy http://localhost:8080>

		Require all granted

		Options none
	</Proxy>

	ErrorLog ${APACHE_LOG_DIR}/expose.domain.tld-error.log
	CustomLog ${APACHE_LOG_DIR}/expose.domain.tld-access.log combined

	SSLCertificateFile /etc/letsencrypt/live/expose.domain.tld-0001/fullchain.pem
	SSLCertificateKeyFile /etc/letsencrypt/live/expose.domain.tld-0001/privkey.pem
	Include /etc/letsencrypt/options-ssl-apache.conf
</VirtualHost>
</IfModule>
```
