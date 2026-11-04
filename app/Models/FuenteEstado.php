<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Estado de salud de la fuente externa de un módulo (tabla fuentes_estado).
 *
 * @property string $modulo
 * @property \Illuminate\Support\Carbon|null $ultima_ok
 * @property \Illuminate\Support\Carbon|null $ultimo_error
 * @property string|null $mensaje_error
 * @property int $intentos_fallidos
 */
class FuenteEstado extends Model
{
    /** Módulos del MVP (docs/01-alcance.md). */
    public const MODULES = ['dolar', 'sismos', 'feriados', 'clima', 'calculadoras', 'ruc', 'dni'];

    protected $table = 'fuentes_estado';

    protected $primaryKey = 'modulo';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'modulo',
        'ultima_ok',
        'ultimo_error',
        'mensaje_error',
        'intentos_fallidos',
    ];

    protected function casts(): array
    {
        return [
            'ultima_ok' => 'datetime',
            'ultimo_error' => 'datetime',
            'intentos_fallidos' => 'integer',
        ];
    }

    /**
     * El módulo tiene un error vigente si el último error es posterior al último éxito.
     */
    public function hasActiveError(): bool
    {
        if ($this->ultimo_error === null) {
            return false;
        }

        return $this->ultima_ok === null || $this->ultimo_error->greaterThan($this->ultima_ok);
    }
}
