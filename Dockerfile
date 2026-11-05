# syntax=docker/dockerfile:1
FROM debian:bookworm-slim

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        nginx \
        supervisor \
        unzip \
    && curl -fsSL https://packages.sury.org/debsuryorg-archive-keyring.deb \
        -o /tmp/debsuryorg-archive-keyring.deb \
    && dpkg -i /tmp/debsuryorg-archive-keyring.deb \
    && printf '%s\n' \
        'deb [signed-by=/usr/share/keyrings/debsuryorg-archive-keyring.gpg] https://packages.sury.org/php/ bookworm main' \
        > /etc/apt/sources.list.d/php.list \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
        php8.3-bcmath \
        php8.3-cli \
        php8.3-curl \
        php8.3-fpm \
        php8.3-intl \
        php8.3-mbstring \
        php8.3-opcache \
        php8.3-pgsql \
        php8.3-redis \
        php8.3-xml \
        php8.3-zip \
    && php -r '$required = ["bcmath", "intl", "pcntl", "pdo_pgsql", "redis", "zip", "Zend OPcache"]; foreach ($required as $extension) { if (! extension_loaded($extension)) { fwrite(STDERR, "Falta la extensión PHP requerida: {$extension}\n"); exit(1); } }' \
    && sed -i 's|^listen = .*|listen = 127.0.0.1:9000|' /etc/php/8.3/fpm/pool.d/www.conf \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/debsuryorg-archive-keyring.deb

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY docker/nginx/default.conf /etc/nginx/sites-available/default
RUN ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --no-scripts \
    --optimize-autoloader

COPY . /var/www/html

RUN composer dump-autoload \
        --no-dev \
        --optimize \
        --no-interaction \
        --no-scripts \
    && mkdir -p \
        storage/app/private \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
