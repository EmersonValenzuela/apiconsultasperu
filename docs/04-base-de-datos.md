# 04 — Base de datos (SQLite en desarrollo, PostgreSQL 16 en producción)

Convenciones: nombres de tablas y columnas en español y en `snake_case`, `id` bigint autoincremental, `created_at` / `updated_at` en todas salvo donde la tabla define otra cosa (`fuentes_estado` usa `modulo` como PK; `consultas_log` usa `creado_en`), fechas con zona horaria `America/Lima`. Cada tabla tiene su migración y, cuando aplique, su seeder.

Portabilidad: las migraciones deben funcionar igual en SQLite y PostgreSQL. Usa `json()` (no `jsonb()`), `timestampTz()`, `decimal()`, y evita SQL crudo o específico de un motor.

## `cotizaciones_moneda`
Histórico de tipo de cambio.

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| moneda | char(3) | ISO 4217 (ej. USD) |
| compra | decimal(10,4) null | |
| venta | decimal(10,4) | |
| tipo | varchar(20) | `oficial` o `referencial` |
| fuente | varchar(60) | |
| fecha | date | |
| obtenido_en | timestamptz | |

Índices: único `(moneda, fecha, fuente)`; índice `(moneda, fecha desc)`.

## `sismos`

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| id_externo | varchar(80) | único (id del evento en la fuente) |
| magnitud | decimal(3,1) | |
| lugar | varchar(255) | |
| profundidad_km | decimal(6,1) null | |
| latitud | decimal(9,6) | |
| longitud | decimal(9,6) | |
| ocurrido_en | timestamptz | |
| fuente | varchar(40) | |
| url | varchar(255) null | |
| crudo | json null | respuesta original, para depurar (`json()` para ser portable SQLite/PostgreSQL) |

Índices: único `id_externo`; índice `ocurrido_en desc`; índice `magnitud`.
Política de retención: conservar 1 año; comando `sismos:purgar` programado semanalmente.

## `feriados`

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| fecha | date | |
| nombre | varchar(150) | |
| tipo | varchar(30) | `feriado`, `dia_no_laborable`, `feriado_puente` |
| anio | smallint | |
| fuente | varchar(60) | `nager`, `manual` |

Único `(fecha, nombre)`. Los días no laborables y puentes se cargan de forma manual (seeder o comando `feriados:agregar`) según lo que publique el gobierno, porque las fuentes automáticas suelen no incluirlos.

## `lugares`
Lugares soportados por el módulo de clima.

| Columna | Tipo |
|---|---|
| id | bigint PK |
| slug | varchar(80) único |
| nombre | varchar(120) |
| departamento | varchar(60) |
| provincia | varchar(60) null |
| distrito | varchar(80) null |
| latitud | decimal(9,6) |
| longitud | decimal(9,6) |
| activo | boolean default true |

El clima actual se guarda en el store de caché configurado (no en BD) con TTL de 60 min. Además se guarda una **copia de larga duración (≥ 6 h)** para servirla con `meta.stale = true` si la fuente falla (Fase 5).

## `parametros`
Valores legales y tributarios configurables (no hardcodear en código).

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| clave | varchar(60) | ej. `rmv`, `uit`, `igv_tasa`, `asignacion_familiar_pct`, `bono_extra_essalud_pct`, `bono_extra_eps_pct` |
| valor | decimal(14,4) | |
| vigente_desde | date | |
| vigente_hasta | date null | |
| fuente_legal | varchar(255) | norma o enlace |
| verificado_en | date | última vez que un humano lo confirmó |

Índice `(clave, vigente_desde desc)`. El Service toma el valor vigente a la fecha de cálculo.

## `contribuyentes` (opcional, Fase RUC avanzada)
Solo si se implementa el plan B con el padrón reducido de SUNAT.

| Columna | Tipo |
|---|---|
| ruc | char(11) PK |
| razon_social | varchar(255) |
| estado | varchar(30) |
| condicion | varchar(30) |
| ubigeo | char(6) null |
| direccion | varchar(255) null |
| actualizado_en | timestamptz |

## `fuentes_estado`
Salud de cada integración, usada por `/health`.

| Columna | Tipo |
|---|---|
| modulo | varchar(30) PK |
| ultima_ok | timestamptz null |
| ultimo_error | timestamptz null |
| mensaje_error | text null |
| intentos_fallidos | integer default 0 |

## `clientes_api` (para la app móvil)

| Columna | Tipo |
|---|---|
| id | bigint PK |
| nombre | varchar(80) |
| clave_hash | varchar(64) (SHA-256 de la clave) |
| activo | boolean |
| limite_por_minuto | integer |

La clave en texto plano se muestra solo una vez al crearla (comando `clientes:crear`).

## `consultas_log` (auditoría sin datos personales)

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| tipo | varchar(10) | `ruc`, `dni` |
| documento_hash | varchar(64) | HMAC-SHA256 con clave de la app, nunca el documento |
| ip_hash | varchar(64) | HMAC de la IP |
| resultado | varchar(20) | `ok`, `no_encontrado`, `error`, `limitado` |
| creado_en | timestamptz | |

Retención: 90 días (`consultas:purgar`).
