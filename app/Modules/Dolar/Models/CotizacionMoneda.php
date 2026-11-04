<?php

namespace App\Modules\Dolar\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $moneda
 * @property float|null $compra
 * @property float $venta
 * @property string $tipo
 * @property string $fuente
 * @property \Illuminate\Support\Carbon $fecha
 * @property \Illuminate\Support\Carbon $obtenido_en
 */
class CotizacionMoneda extends Model
{
    protected $table = 'cotizaciones_moneda';

    protected $fillable = [
        'moneda',
        'compra',
        'venta',
        'tipo',
        'fuente',
        'fecha',
        'obtenido_en',
    ];

    protected function casts(): array
    {
        return [
            'compra' => 'float',
            'venta' => 'float',
            'fecha' => 'date:Y-m-d',
            'obtenido_en' => 'datetime',
        ];
    }
}
