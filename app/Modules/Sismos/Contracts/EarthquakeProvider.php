<?php

namespace App\Modules\Sismos\Contracts;

interface EarthquakeProvider
{
    /**
     * Obtiene los sismos recientes para la región de Perú desde la fuente externa.
     *
     * @param  int  $days  Días hacia atrás (1–30)
     * @param  float  $minMagnitude  Magnitud mínima
     * @param  int  $limit  Límite de registros
     * @return array<int, array{
     *     id_externo: string,
     *     magnitud: float,
     *     lugar: string,
     *     profundidad_km: float|null,
     *     latitud: float,
     *     longitud: float,
     *     ocurrido_en: \Illuminate\Support\Carbon,
     *     fuente: string,
     *     url: string|null,
     *     crudo: array<string, mixed>
     * }>
     *
     * @throws \App\Support\Exceptions\SourceUnavailableException Si la fuente falla.
     */
    public function getRecentEarthquakes(int $days = 7, float $minMagnitude = 0, int $limit = 100): array;
}
