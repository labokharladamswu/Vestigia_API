FROM php:8.3-apache

# PostgreSQL driver for PDO (Supabase). curl/openssl/fileinfo already ship with the image.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-vestigia.ini
COPY docker/apache.conf /etc/apache2/conf-available/vestigia.conf
RUN a2enconf vestigia

# Only /api is exposed over HTTP; lib/ (helpers) stays outside the web root.
WORKDIR /var/www/app
COPY api/ /var/www/app/api/
COPY lib/ /var/www/app/lib/

EXPOSE 80
