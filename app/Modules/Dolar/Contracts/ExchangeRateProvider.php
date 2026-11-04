<?php

namespace App\Modules\Dolar\Contracts;

interface ExchangeRateProvider
{
    /**
     * Obtiene las tasas de cambio vigentes para la moneda base indicada.
     *
     * @param  string  $base  Código ISO 4217 de la moneda base (ej. 'USD')
     * @return array{
     *     fuente: string,
     *     base: string,
     *     fecha: string,
     *     actualizado_en: \Illuminate\Support\Carbon,
     *     tipo: string,
     *     tasas: array<string, float>
     * }
     *
     * @throws \App\Support\Exceptions\SourceUnavailableException Si la fuente no responde o falla.
     */
    public function getRates(string $base = 'USD'): array;
}
