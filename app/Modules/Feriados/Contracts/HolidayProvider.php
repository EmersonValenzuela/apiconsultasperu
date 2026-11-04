<?php

namespace App\Modules\Feriados\Contracts;

interface HolidayProvider
{
    /**
     * Obtiene los feriados oficiales para Perú para un año dado desde la fuente externa.
     *
     * @return array<int, array{
     *     fecha: string,
     *     nombre: string,
     *     tipo: string,
     *     anio: int,
     *     fuente: string
     * }>
     *
     * @throws \App\Support\Exceptions\SourceUnavailableException Si la fuente no responde.
     */
    public function getHolidays(int $year): array;
}
