<?php

namespace App\Modules\Combustibles\Models;

use Illuminate\Database\Eloquent\Model;

class PrecioCombustible extends Model
{
    protected $table = 'precios_combustibles';

    protected $fillable = [
        'region',
        'tipo',
        'nombre',
        'octanaje',
        'unidad',
        'simbolo_unidad',
        'precio_promedio',
        'precio_minimo',
        'precio_maximo',
        'fuente',
        'vigencia_desde',
    ];

    protected $casts = [
        'precio_promedio' => 'float',
        'precio_minimo' => 'float',
        'precio_maximo' => 'float',
        'vigencia_desde' => 'date:Y-m-d',
    ];
}
