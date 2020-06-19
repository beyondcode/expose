FROM php:7.4-cli

RUN apt-get update
RUN apt-get install -y git libzip-dev zip

RUN docker-php-ext-install zip

# install composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && rm composer-setup.php

COPY . /app

# install the dependencies
RUN cd /app && composer install -o --prefer-dist && chmod a+x /app/expose

ENTRYPOINT ["/app/expose"]
