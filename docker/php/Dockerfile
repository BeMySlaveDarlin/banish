FROM php:8.3-fpm AS php_base

ARG APP_PORT
ARG TIMEZONE
ARG COMPOSER_VERSION

RUN apt-get update && apt-get install -y \
    gnupg g++ git procps openssl mc nano make acl \
    curl wget unzip gettext software-properties-common \
    zlib1g-dev libzip-dev libonig-dev libxslt1-dev \
    libicu-dev libpq-dev libmemcached-dev libmemcached11

RUN ln -snf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && echo ${TIMEZONE} > /etc/timezone \
    && printf '[PHP]\ndate.timezone = "%s"\n', ${TIMEZONE} > /usr/local/etc/php/conf.d/tzone.ini \
    && "date"

RUN docker-php-ext-install  \
    zip xsl intl xml exif bcmath  \
    mbstring pcntl calendar shmop \
    opcache sysvmsg sysvsem sysvshm sockets

RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql pgsql

RUN pecl update-channels
RUN pecl install redis
RUN pecl install igbinary
RUN pecl install memcached
RUN docker-php-ext-enable redis igbinary memcached

COPY --link conf.d/app.ini $PHP_INI_DIR/conf.d/
RUN sed -i "s/pm.max_children = 5/pm.max_children = 200/" /usr/local/etc/php-fpm.d/www.conf \
    && sed -i "s/pm = dynamic/pm = static/" /usr/local/etc/php-fpm.d/www.conf \
    && sed -i "s/pm.start_servers = 2/;pm.start_servers = 2/" /usr/local/etc/php-fpm.d/www.conf \
    && sed -i "s/pm.min_spare_servers = 1/;pm.min_spare_servers = 1/" /usr/local/etc/php-fpm.d/www.conf \
    && sed -i "s/pm.max_spare_servers = 3/;pm.max_spare_servers = 3/" /usr/local/etc/php-fpm.d/www.conf \
    && sed -i "s/;error_log = log\/php-fpm.log/error_log = \/var\/log\/php-fpm.log/" /usr/local/etc/php-fpm.conf \
    && sed -i "s/;emergency_restart_threshold = 0/emergency_restart_threshold = 32/" /usr/local/etc/php-fpm.conf \
    && sed -i "s/;emergency_restart_interval = 0/emergency_restart_interval = 1m/" /usr/local/etc/php-fpm.conf \
    && sed -i "s/;process_control_timeout = 0/process_control_timeout = 10s/" /usr/local/etc/php-fpm.conf \
    && sed -i "s/; process.max = 128/process.max = 1024/" /usr/local/etc/php-fpm.conf \
    && sed -i "s/;rlimit_files = 1024/rlimit_files = 65536/" /usr/local/etc/php-fpm.conf \
    && sed -i "s/;rlimit_core = 0/rlimit_core = 0/" /usr/local/etc/php-fpm.conf \
    && sed -i "s/;pm.status_path = \/status/pm.status_path = \/status/" /usr/local/etc/php-fpm.conf \
    && sed -i "s/;ping.path = \/ping/ping.path = \/ping/" /usr/local/etc/php-fpm.conf

RUN php -r "readfile('https://getcomposer.org/installer');" |  \
    php -- --install-dir=/usr/bin/ --filename=composer --version=${COMPOSER_VERSION} \
    && mkdir /run/php

RUN echo 'alias console="php bin/console"' >> ~/.bashrc

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PATH /root/composer/vendor/bin:/var/www/app/vendor/bin:/var/www/app/bin:${PATH}

WORKDIR /var/www/app

EXPOSE $APP_PORT

FROM php_base AS php_dev

ENV APP_ENV=dev XDEBUG_MODE=off

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
COPY --link conf.d/app.dev.ini $PHP_INI_DIR/conf.d/

RUN pecl update-channels
RUN pecl install xdebug
RUN docker-php-ext-enable xdebug

RUN mkdir -p /var/www/app/public/phpca \
    && composer install \
    && composer dump-autoload --classmap-authoritative \
    && composer dump-env dev \
    && composer run-script post-install-cmd \
    && chmod +x bin/console; sync

FROM php_base AS php_prod

ENV APP_ENV=prod

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY --link conf.d/app.prod.ini $PHP_INI_DIR/conf.d/

RUN mkdir -p /var/www/app/public/phpca \
    && composer install --no-cache --prefer-dist --no-dev --no-autoloader --no-scripts \
    && composer dump-autoload --classmap-authoritative --no-dev \
    && composer dump-env prod \
    && composer run-script --no-dev post-install-cmd \
    && chmod +x bin/console; sync

FROM php_dev AS php_dev_supervisor

RUN apt-get update && apt-get install -y supervisor
RUN mkdir -p /var/log/supervisor

COPY --link supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf", "--user", "www-data"]

FROM php_prod AS php_prod_supervisor

RUN apt-get update && apt-get install -y supervisor
RUN mkdir -p /var/log/supervisor

COPY --link supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf", "--user", "www-data"]
