#!/bin/sh
set -e

ROLE=${CONTAINER_ROLE:-app}

echo "Iniciando contenedor Consulta Hoy Perú con rol: $ROLE"

if [ "$ROLE" = "app" ]; then
    echo "Ejecutando migraciones automáticas..."
    php artisan migrate --force

    echo "Asegurando parámetros base del sistema..."
    php artisan db:seed --class=ParametrosSeeder --force || true
    php artisan db:seed --class=Feriados2026Seeder --force || true

    if [ "$APP_ENV" = "production" ]; then
        echo "Optimizando cachés para producción..."
        php artisan config:cache
        php artisan route:cache
    fi

    echo "Iniciando servidor web Nginx + PHP-FPM..."
    exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf

elif [ "$ROLE" = "worker" ]; then
    echo "Iniciando worker de colas Laravel..."
    exec php artisan queue:work --verbose --tries=3 --timeout=90

elif [ "$ROLE" = "scheduler" ]; then
    echo "Iniciando planificador de tareas (Scheduler)..."
    exec php artisan schedule:work

else
    echo "Ejecutando comando personalizado: $@"
    exec "$@"
fi
