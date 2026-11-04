<?php

namespace App\Modules\Ruc\Contracts;

interface RucProvider
{
    /**
     * Nombre identificador del driver (ej. 'peru_consult', 'padron_sunat').
     */
    public function name(): string;

    /**
     * Consulta los datos públicos del RUC ante la fuente configurada.
     *
     * @param  string  $ruc  RUC de 11 dígitos previamente validado
     * @return array{
     *     ruc: string,
     *     razon_social: string,
     *     estado: string,
     *     condicion: string,
     *     direccion: string|null,
     *     departamento: string|null,
     *     provincia: string|null,
     *     distrito: string|null,
     *     fuente: string
     * }|null Devuelve null si no fue encontrado (404).
     *
     * @throws \App\Support\Exceptions\SourceUnavailableException Si el proveedor está caído o falla la conexión.
     */
    public function find(string $ruc): ?array;
}
