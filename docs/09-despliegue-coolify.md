# 09 — Guía de Despliegue en Coolify (Docker, PostgreSQL 16 y Redis)

Esta guía describe cómo desplegar el backend de **Consulta Hoy Perú** en producción utilizando [Coolify](https://coolify.io).

Para una guía visual paso a paso que cubre la instalación de Coolify y el despliegue separado del frontend, backend, PostgreSQL y Redis, consulta [Guía de despliegue en Coolify (HTML)](./guia-despliegue-coolify.html).

---

## 1. Arquitectura de Despliegue

El despliegue consta de 5 servicios orquestados vía Docker:
1. **`app`**: API REST Laravel servida por Nginx + PHP 8.3-FPM (Debian Bookworm).
2. **`worker`**: Proceso de colas background (`php artisan queue:work --tries=3`).
3. **`scheduler`**: Planificador de tareas (`php artisan schedule:work`).
4. **`postgres`**: Base de datos relacional PostgreSQL 16.
5. **`redis`**: Almacén en memoria persistente para Caché, Colas y Rate Limiting.

---

## 2. Variables de Entorno de Producción (`.env`)

En el panel de Coolify, configura las siguientes variables de entorno:

### Aplicación
```ini
APP_NAME="Consulta Hoy Peru"
APP_ENV=production
APP_KEY=base64:GENERA_CON_PHP_ARTISAN_KEY_GENERATE
APP_DEBUG=false
APP_URL=https://api-consultas.safezoneperu.com
PORT=80
```

### Base de Datos (PostgreSQL 16)
```ini
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=consultahoy
DB_USERNAME=postgres
DB_PASSWORD=CLAVE_SUPER_SEGURA_AQUI
```

En el despliegue Docker Compose, `DB_HOST` y `DB_PORT` pueden cambiarse para usar un servidor PostgreSQL externo; por defecto apuntan al servicio `postgres` de Compose. La contraseña debe configurarse como variable/secret de Coolify y ser no vacía. Compose no usa contraseñas predeterminadas ni autenticación `trust`.

### Caché, Colas y Sesiones (Redis 7)
```ini
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_HOST=redis
REDIS_PORT=6379
```

### Seguridad y CORS
```ini
CORS_ALLOWED_ORIGINS="https://consultasperu.safezoneperu.com"
RATE_LIMIT_GENERAL=60
RATE_LIMIT_CALCULADORAS=30
RATE_LIMIT_RUC=20
RATE_LIMIT_DNI=5
```

### Proveedores Externos
```ini
DOLAR_DRIVER=open_er_api
SISMOS_DRIVER=usgs
FERIADOS_DRIVER=nager

RUC_DRIVERS=peru_consult,padron_sunat
PERU_CONSULT_URL=http://peru-consult-api:8080
PERU_CONSULT_TOKEN=TU_TOKEN_INTERNO_SEGURO

DNI_ENABLED=false
DNI_DRIVER=peru_consult
TURNSTILE_SECRET_KEY=TU_CLAVE_SECRETA_CLOUDFLARE_TURNSTILE
```

---

## 3. Despliegue en Coolify

### Opción A: Despliegue mediante `docker-compose.yml` (Recomendada)
1. En Coolify, crea un nuevo **Service** de tipo **Docker Compose**.
2. Apunta al repositorio Git de este proyecto en la rama `main`.
3. Coolify detectará automáticamente `docker-compose.yml`.
4. Asigna las variables de entorno listadas arriba en la pestaña **Environment Variables**.
5. Conecta el dominio `api.tudominio.pe` al servicio `app` (puerto 80).
6. Presiona **Deploy**.

La imagen instala PHP 8.3 y sus extensiones desde paquetes binarios precompilados de Debian/Sury; no ejecuta `docker-php-ext-install` ni PECL durante el build. El build valida que `bcmath`, `intl`, `opcache`, `pcntl`, `pdo_pgsql`, `redis` y `zip` estén cargadas antes de continuar. El tiempo total también depende de la descarga de paquetes y de Composer, especialmente en un build sin caché.

PostgreSQL inicializa `POSTGRES_DB`, `POSTGRES_USER` y `POSTGRES_PASSWORD` solamente cuando su volumen de datos está vacío. Si se conservó el volumen entre despliegues, cambiar estas variables no cambia la contraseña existente: configura en Coolify la contraseña que ya tiene el usuario o actualízala en PostgreSQL antes de cambiar el secreto. No borres el volumen para resolver un error de autenticación, porque contiene los datos de producción.

### Opción B: Despliegue como Aplicación Individual con Bases Gestionadas
Si prefieres usar una base de datos PostgreSQL y Redis gestionada externamente por Coolify:
1. Crea una base de datos PostgreSQL 16 y un Redis en Coolify.
2. Crea una aplicación desde el repositorio Git seleccionando **Dockerfile**.
3. Configura `DB_HOST`, `DB_PASSWORD`, `REDIS_HOST`, etc., apuntando a los servicios gestionados.
4. Para el worker y scheduler, crea dos instancias adicionales de la aplicación en Coolify cambiando la variable `CONTAINER_ROLE=worker` y `CONTAINER_ROLE=scheduler` respectivamente.

---

## 4. Ciclo de Vida del Contenedor (`entrypoint.sh`)

Al iniciar el contenedor `app`:
1. Ejecuta migraciones automáticas (`php artisan migrate --force`).
2. Siembra parámetros base iniciales (`ParametrosSeeder` y `Feriados2026Seeder`).
3. Compila cachés de alto rendimiento de Laravel (`php artisan config:cache` y `php artisan route:cache`).
4. Inicia Nginx y PHP-FPM bajo `supervisord`.

---

## 5. Verificación de Salud en Producción

Una vez desplegado, verifica la salud del sistema:

```bash
curl -I https://api.tudominio.pe/api/v1/health
```

Debe responder HTTP `200 OK` con:
```json
{
  "data": {
    "estado": "ok",
    "base_de_datos": { "ok": true, "conexion": "pgsql" },
    "cache": { "ok": true, "store": "redis" },
    "modulos": { ... }
  }
}
```

---

## 6. Generar Clave para la App Móvil en Producción

Ingresa a la consola del contenedor `app` desde Coolify o por SSH y ejecuta:

```bash
php artisan clientes:crear "App Android Produccion" --limite=180
```
Copia y guarda la clave generada de forma segura.
