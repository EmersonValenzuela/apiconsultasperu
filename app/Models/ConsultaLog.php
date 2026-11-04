<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $tipo
 * @property string $documento_hash
 * @property string $ip_hash
 * @property string $resultado
 * @property \Illuminate\Support\Carbon $creado_en
 */
class ConsultaLog extends Model
{
    public const TIPO_RUC = 'ruc';

    public const TIPO_DNI = 'dni';

    public const RESULTADO_OK = 'ok';

    public const RESULTADO_NOT_FOUND = 'no_encontrado';

    public const RESULTADO_ERROR = 'error';

    public const RESULTADO_LIMITED = 'limitado';

    protected $table = 'consultas_log';

    public $timestamps = false;

    protected $fillable = [
        'tipo',
        'documento_hash',
        'ip_hash',
        'resultado',
        'creado_en',
    ];

    protected function casts(): array
    {
        return [
            'creado_en' => 'datetime',
        ];
    }
}
