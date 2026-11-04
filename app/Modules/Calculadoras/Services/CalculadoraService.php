<?php

namespace App\Modules\Calculadoras\Services;

use App\Modules\Calculadoras\Models\Parametro;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use RuntimeException;

class CalculadoraService
{
    /**
     * Obtiene el valor vigente de un parámetro legal para una fecha dada.
     *
     * @throws RuntimeException Si el parámetro no está configurado.
     */
    public function getParametro(string $clave, ?string $fecha = null): BigDecimal
    {
        $fecha = $fecha ?? now('America/Lima')->format('Y-m-d');

        /** @var Parametro|null $parametro */
        $parametro = Parametro::query()
            ->where('clave', $clave)
            ->where('vigente_desde', '<=', $fecha)
            ->where(function ($q) use ($fecha) {
                $q->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', $fecha);
            })
            ->orderByDesc('vigente_desde')
            ->first();

        if ($parametro === null) {
            throw new RuntimeException("El parámetro legal '{$clave}' no está configurado en la base de datos para la fecha {$fecha}.");
        }

        return BigDecimal::of($parametro->valor);
    }

    /**
     * Calcula el IGV según docs/06-calculadoras.md.
     *
     * @param  float|numeric-string  $monto
     * @param  string  $tipo  'base' | 'total'
     * @return array{base: float, igv: float, total: float, tasa: float}
     */
    public function calcularIgv(float|string $monto, string $tipo = 'base', ?string $fecha = null): array
    {
        $tasa = $this->getParametro('igv_tasa', $fecha);
        $montoDec = BigDecimal::of($monto);

        if ($tipo === 'total') {
            $divisor = BigDecimal::of(1)->plus($tasa);
            $base = $montoDec->dividedBy($divisor, 6, RoundingMode::HalfUp);
            $igv = $montoDec->minus($base);
            $total = $montoDec;
        } else {
            $base = $montoDec;
            $igv = $base->multipliedBy($tasa);
            $total = $base->plus($igv);
        }

        return [
            'base' => (float) (string) $base->toScale(2, RoundingMode::HalfUp),
            'igv' => (float) (string) $igv->toScale(2, RoundingMode::HalfUp),
            'total' => (float) (string) $total->toScale(2, RoundingMode::HalfUp),
            'tasa' => (float) (string) $tasa->toScale(2, RoundingMode::HalfUp),
        ];
    }

    /**
     * Calcula la Gratificación legal y bonificación extraordinaria (julio / diciembre).
     *
     * @param  float|numeric-string  $sueldo
     * @param  int  $mesesTrabajados  1 a 6
     * @param  float|numeric-string  $promedioVariables
     * @param  string  $tipoSeguro  'essalud' | 'eps'
     * @return array{remuneracion_computable: float, gratificacion: float, bonificacion_extraordinaria: float, total: float}
     */
    public function calcularGratificacion(
        float|string $sueldo,
        bool $asignacionFamiliar = false,
        int $mesesTrabajados = 6,
        float|string $promedioVariables = 0,
        string $tipoSeguro = 'essalud',
        ?string $fecha = null,
    ): array {
        $rc = $this->calcularRemuneracionComputable($sueldo, $asignacionFamiliar, $promedioVariables, $fecha);

        // gratificacion = (RC / 6) * meses_trabajados
        $gratificacion = $rc->dividedBy(BigDecimal::of(6), 6, RoundingMode::HalfUp)
            ->multipliedBy(BigDecimal::of($mesesTrabajados));

        $bonoClave = $tipoSeguro === 'eps' ? 'bono_extra_eps_pct' : 'bono_extra_essalud_pct';
        $bonoPct = $this->getParametro($bonoClave, $fecha);

        // bonificacion_extraordinaria = gratificacion * bono_pct
        $bonoExtra = $gratificacion->multipliedBy($bonoPct);
        $total = $gratificacion->plus($bonoExtra);

        return [
            'remuneracion_computable' => (float) (string) $rc->toScale(2, RoundingMode::HalfUp),
            'gratificacion' => (float) (string) $gratificacion->toScale(2, RoundingMode::HalfUp),
            'bonificacion_extraordinaria' => (float) (string) $bonoExtra->toScale(2, RoundingMode::HalfUp),
            'total' => (float) (string) $total->toScale(2, RoundingMode::HalfUp),
        ];
    }

    /**
     * Calcula la Compensación por Tiempo de Servicios (CTS).
     *
     * @param  float|numeric-string  $sueldo
     * @param  float|numeric-string  $ultimaGratificacion
     * @param  int  $meses  0 a 6
     * @param  int  $dias  0 a 29
     * @param  float|numeric-string  $promedioVariables
     * @return array{remuneracion_computable: float, cts_por_meses: float, cts_por_dias: float, total: float}
     */
    public function calcularCts(
        float|string $sueldo,
        bool $asignacionFamiliar = false,
        float|string $ultimaGratificacion = 0,
        int $meses = 6,
        int $dias = 0,
        float|string $promedioVariables = 0,
        ?string $fecha = null,
    ): array {
        $rc = $this->calcularRemuneracionComputable($sueldo, $asignacionFamiliar, $promedioVariables, $fecha);

        // RC_cts = RC + (ultima_gratificacion / 6)
        $unSextoGrati = BigDecimal::of($ultimaGratificacion)->dividedBy(BigDecimal::of(6), 6, RoundingMode::HalfUp);
        $rcCts = $rc->plus($unSextoGrati);

        // cts_por_meses = (RC_cts / 12) * meses
        $ctsPorMeses = $rcCts->dividedBy(BigDecimal::of(12), 6, RoundingMode::HalfUp)
            ->multipliedBy(BigDecimal::of($meses));

        // cts_por_dias = (RC_cts / 360) * dias
        $ctsPorDias = $rcCts->dividedBy(BigDecimal::of(360), 6, RoundingMode::HalfUp)
            ->multipliedBy(BigDecimal::of($dias));

        $ctsPorMesesRounded = $ctsPorMeses->toScale(2, RoundingMode::HalfUp);
        $ctsPorDiasRounded = $ctsPorDias->toScale(2, RoundingMode::HalfUp);
        $totalRounded = $ctsPorMesesRounded->plus($ctsPorDiasRounded);

        return [
            'remuneracion_computable' => (float) (string) $rcCts->toScale(2, RoundingMode::HalfUp),
            'cts_por_meses' => (float) (string) $ctsPorMesesRounded,
            'cts_por_dias' => (float) (string) $ctsPorDiasRounded,
            'total' => (float) (string) $totalRounded,
        ];
    }

    /**
     * Remuneración Computable (RC) = sueldo básico + asignación familiar + promedio variables.
     */
    public function calcularRemuneracionComputable(
        float|string $sueldo,
        bool $asignacionFamiliar = false,
        float|string $promedioVariables = 0,
        ?string $fecha = null,
    ): BigDecimal {
        $sueldoDec = BigDecimal::of($sueldo);
        $variablesDec = BigDecimal::of($promedioVariables);

        $asigFamDec = BigDecimal::of(0);
        if ($asignacionFamiliar) {
            $rmv = $this->getParametro('rmv', $fecha);
            $asigPct = $this->getParametro('asignacion_familiar_pct', $fecha);
            $asigFamDec = $rmv->multipliedBy($asigPct);
        }

        return $sueldoDec->plus($asigFamDec)->plus($variablesDec);
    }
}
