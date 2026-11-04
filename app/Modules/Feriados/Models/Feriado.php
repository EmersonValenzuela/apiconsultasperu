<?php

namespace App\Modules\Feriados\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property \Illuminate\Support\Carbon $fecha
 * @property string $nombre
 * @property string $tipo
 * @property int $anio
 * @property string $fuente
 */
class Feriado extends Model
{
    public const TIPO_FERIADO = 'feriado';

    public const TIPO_DIA_NO_LABORABLE = 'dia_no_laborable';

    public const TIPO_FERIADO_PUENTE = 'feriado_puente';

    public const FUENTE_NAGER = 'nager';

    public const FUENTE_MANUAL = 'manual';

    protected $table = 'feriados';

    protected $fillable = [
        'fecha',
        'nombre',
        'tipo',
        'anio',
        'fuente',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date:Y-m-d',
            'anio' => 'integer',
        ];
    }
}
