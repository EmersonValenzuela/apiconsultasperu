# syntax=docker/dockerfile:1
FROM php:8.3-fpm-alpine

# Instalar dependencias del sistema y extensiones de PHP requeridas
RUN apk add --no-cache \
    nginx \
    supervisor \
    postgresql-dev \
    libzip-dev \
    sqlite-dev \
    icu-dev \
    curl \
    git \
    $PHPIZE_DEPS \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        pdo_sqlite \
        bcmath \
        opcache \
        zip \
        pcntl \
        intl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS \
    && rm -rf /tmp/pear

# Copiar Composer binario oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configurar directorio de trabajo
WORKDIR /var/www/html

# Copiar configuraciones de Nginx y Supervisord
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Copiar código de la aplicación
COPY . /var/www/html

# Instalar dependencias de producción de Composer
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Permisos para storage y bootstrap/cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
