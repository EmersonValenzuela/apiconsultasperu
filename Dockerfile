# syntax=docker/dockerfile:1
FROM php:8.3-fpm-alpine

# Herramienta oficial para instalar extensiones de PHP precompiladas (evita compilar C/C++)
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# Instalar Nginx, Supervisor y extensiones PHP mediante binarios directos
RUN apk add --no-cache nginx supervisor curl git \
    && install-php-extensions \
        pdo_pgsql \
        pgsql \
        pdo_sqlite \
        bcmath \
        opcache \
        zip \
        pcntl \
        intl \
        redis

# Copiar Composer binario oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configurar directorio de trabajo
WORKDIR /var/www/html

# Copiar configuraciones de Nginx, Supervisord y entrypoint
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Copiar archivos de dependencias primero para aprovechar la capa de caché de Docker
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# Copiar código de la aplicación (excluyendo lo indicado en .dockerignore)
COPY . /var/www/html

# Generar autoloader optimizado de Composer
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

# Permisos para storage y bootstrap/cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
