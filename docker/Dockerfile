FROM php:8.4-apache

ARG var_timezone
ARG var_uid
ARG var_gid
ARG http_proxy

ENV CURRENT_UID=$var_uid
ENV CURRENT_GID=$var_gid
ENV TIMEZONE=$var_timezone

RUN usermod -u $CURRENT_UID www-data && groupmod -g $CURRENT_GID www-data
RUN chown -R www-data:www-data /var/www

RUN ln -sf /usr/share/zoneinfo/"${TIMEZONE}" /etc/localtime

COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates curl gnupg \
    && curl -fsSL https://deb.nodesource.com/setup_24.x | bash - \
    && apt-get install -y --no-install-recommends nodejs git zip unzip \
    && rm -rf /var/lib/apt/lists/*

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libpq-dev \
        libicu-dev \
        libssl-dev \
        zlib1g-dev \
        libxml2-dev \
        libzip-dev \
        libldb-dev \
        libldap2-dev \
        libonig-dev \
        libjpeg-dev libpng-dev libwebp-dev libxpm-dev \
        libcurl4-gnutls-dev \
    && rm -rf /var/lib/apt/lists/*

RUN if [ -n "${http_proxy}" ]; then pear config-set http_proxy "${http_proxy}"; fi \
    && pecl install apcu xdebug solr \
    && docker-php-ext-configure gd --with-webp --with-jpeg \
    && docker-php-ext-install -j$(nproc) zip opcache intl pdo_pgsql pgsql ldap xml gd mbstring \
    && docker-php-ext-enable apcu pdo_pgsql sodium xdebug solr

RUN echo "zend_extension=xdebug.so" > /usr/local/etc/php/conf.d/custom.ini \
    && echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "xdebug.client_port=9003" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "xdebug.log=/tmp/xdebug.log" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "xdebug.log_level=10" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "date.timezone=\"Europe/Berlin\"" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "memory_limit=1024M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "upload_max_filesize = 200M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "post_max_size = 200M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "max_execution_time = 20" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "opcache.enable_cli=0" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "opcache.revalidate_freq=10" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "opcache.validate_timestamps=1" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "opcache.memory_consumption=192" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "opcache.max_wasted_percentage=10" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "opcache.interned_strings_buffer=1" >> /usr/local/etc/php/conf.d/custom.ini

RUN npm install --global yarn
# && npm install -g npm@10.5.0
RUN apt clean && apt autoremove -y && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*
RUN a2enmod rewrite
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/app/public|g' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's|<Directory /var/www/>|<Directory /var/www/app/public/>|g' /etc/apache2/apache2.conf \
    && sed -i 's|AllowOverride None|AllowOverride All|g' /etc/apache2/apache2.conf

WORKDIR /var/www
USER www-data

EXPOSE 80

CMD ["apache2-foreground"]
