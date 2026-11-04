<?php

namespace App\Modules\Calculadoras\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $clave
 * @property string $valor
 * @property \Illuminate\Support\Carbon $vigente_desde
 * @property \Illuminate\Support\Carbon|null $vigente_hasta
 * @property string $fuente_legal
 * @property \Illuminate\Support\Carbon $verificado_en
 */
class Parametro extends Model
{
    protected $table = 'parametros';

    protected $fillable = [
        'clave',
        'valor',
        'vigente_desde',
        'vigente_hasta',
        'fuente_legal',
        'verificado_en',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'string',
            'vigente_desde' => 'date:Y-m-d',
            'vigente_hasta' => 'date:Y-m-d',
            'verificado_en' => 'date:Y-m-d',
        ];
    }
}
