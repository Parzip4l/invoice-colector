FROM php:8.3-cli

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libldap2-dev \
        libpng-dev \
        libpq-dev \
        unzip \
        zip \
    && docker-php-ext-configure ldap --with-libdir="lib/$(dpkg-architecture --query DEB_HOST_MULTIARCH)" \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        bcmath \
        gd \
        ldap \
        pdo_pgsql \
        pgsql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php/entrypoint.sh /usr/local/bin/invoice-collector-entrypoint
RUN chmod +x /usr/local/bin/invoice-collector-entrypoint

EXPOSE 8000

ENTRYPOINT ["invoice-collector-entrypoint"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
