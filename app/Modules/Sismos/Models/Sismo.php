<?php

namespace App\Modules\Sismos\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $id_externo
 * @property float $magnitud
 * @property string $lugar
 * @property float|null $profundidad_km
 * @property float $latitud
 * @property float $longitud
 * @property \Illuminate\Support\Carbon $ocurrido_en
 * @property string $fuente
 * @property string|null $url
 * @property array|null $crudo
 */
class Sismo extends Model
{
    protected $table = 'sismos';

    protected $fillable = [
        'id_externo',
        'magnitud',
        'lugar',
        'profundidad_km',
        'latitud',
        'longitud',
        'ocurrido_en',
        'fuente',
        'url',
        'crudo',
    ];

    protected function casts(): array
    {
        return [
            'magnitud' => 'float',
            'profundidad_km' => 'float',
            'latitud' => 'float',
            'longitud' => 'float',
            'ocurrido_en' => 'datetime',
            'crudo' => 'array',
        ];
    }
}
