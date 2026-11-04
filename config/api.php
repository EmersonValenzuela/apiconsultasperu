<?php

/*
|--------------------------------------------------------------------------
| Configuración de la API (Consulta Hoy Perú)
|--------------------------------------------------------------------------
*/

return [

    /*
    | Límites por IP por minuto (docs/07-seguridad-y-legal.md).
    | Se aplican sobre el store de caché configurado (CACHE_STORE).
    | Los grupos ruc, dni y calculadoras se activan en sus fases.
    */
    'rate_limits' => [
        'general' => (int) env('RATE_LIMIT_GENERAL', 60),
        'calculadoras' => (int) env('RATE_LIMIT_CALCULADORAS', 30),
        'ruc' => (int) env('RATE_LIMIT_RUC', 20),
    ],

];
