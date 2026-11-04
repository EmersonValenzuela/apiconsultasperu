<?php

namespace App\Modules\Ruc\Clients;

use App\Modules\Ruc\Contracts\RucProvider;
use App\Support\Exceptions\SourceUnavailableException;

/**
 * Driver de respaldo para consultar el RUC mediante el padrón reducido de SUNAT (Plan B).
 *
 * Funcionamiento proyectado:
 * 1. Importa periódicamente el archivo de padrón reducido que publica SUNAT a la tabla `contribuyentes`.
 * 2. Consulta los datos de contribuyentes directamente en la base de datos local.
 *
 * Por implementar cuando se requiera activar el Plan B sin depender de APIs en línea de SUNAT.
 */
class PadronSunatRucClient implements RucProvider
{
    public const DRIVER_NAME = 'padron_sunat';

    public function name(): string
    {
        return self::DRIVER_NAME;
    }

    public function find(string $ruc): ?array
    {
        // Por implementar: consulta a la tabla `contribuyentes`
        throw new SourceUnavailableException('El driver padron_sunat aún no está habilitado.');
    }
}
