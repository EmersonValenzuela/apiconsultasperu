<?php

namespace App\Modules\Dni\Contracts;

interface DniProvider
{
    /**
     * Identificador del driver (ej. 'peru_consult').
     */
    public function name(): string;

    /**
     * Consulta los datos de un DNI en la fuente externa.
     *
     * @return array{
     *     dni: string,
     *     nombres: string,
     *     apellido_paterno: string,
     *     apellido_materno: string,
     *     fuente: string
     * }|null Retorna null si el DNI no existe o fue dado de baja.
     *
     * @throws \App\Support\Exceptions\SourceUnavailableException Si la fuente no responde.
     */
    public function find(string $dni): ?array;
}
