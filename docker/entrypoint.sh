#!/bin/sh
set -e

ROLE=${CONTAINER_ROLE:-app}

echo "Iniciando contenedor Consulta Hoy Perú con rol: $ROLE"

if [ "$ROLE" = "app" ]; then
    echo "Ejecutando migraciones automáticas..."
    php artisan migrate --force

    echo "Asegurando parámetros base, feriados, combustibles y estado de fuentes..."
    php artisan db:seed --class=ParametrosSeeder --force || true
    php artisan db:seed --class=FeriadosSeeder --force || true
    php artisan db:seed --class=CombustiblesSeeder --force || true
    php artisan db:seed --class=FuentesEstadoSeeder --force || true

    echo "Sincronizando datos externos iniciales (Dólar, Sismos, Feriados)..."
    php artisan dolar:sincronizar || true
    php artisan sismos:sincronizar || true
    php artisan feriados:sincronizar || true

    if [ "$APP_ENV" = "production" ]; then
        echo "Optimizando cachés para producción..."
        php artisan config:cache
        php artisan route:cache
    fi

    echo "Iniciando servicios con Supervisord (Nginx + PHP-FPM + Scheduler + Worker)..."
    exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf

elif [ "$ROLE" = "worker" ]; then
    echo "Iniciando worker dedicado de colas Laravel..."
    exec php artisan queue:work --verbose --tries=3 --timeout=90

elif [ "$ROLE" = "scheduler" ]; then
    echo "Iniciando planificador dedicado de tareas (Scheduler)..."
    exec php artisan schedule:work

else
    echo "Ejecutando comando personalizado: $@"
    exec "$@"
fi
