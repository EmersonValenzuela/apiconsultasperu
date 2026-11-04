<?php

namespace App\Modules\Ruc\Support;

class RucValidator
{
    private const FACTORS = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];

    private const VALID_PREFIXES = ['10', '15', '16', '17', '20'];

    /**
     * Valida si un número de RUC es sintáctica y matemáticamente válido según el Módulo 11 de SUNAT.
     */
    public static function validate(mixed $ruc): bool
    {
        if (! is_string($ruc) && ! is_numeric($ruc)) {
            return false;
        }

        $ruc = trim((string) $ruc);

        // 1. Exactamente 11 dígitos numéricos
        if (! preg_match('/^[0-9]{11}$/', $ruc)) {
            return false;
        }

        // 2. Prefijo de tipo de contribuyente
        $prefix = substr($ruc, 0, 2);
        if (! in_array($prefix, self::VALID_PREFIXES, true)) {
            return false;
        }

        // 3. Ponderación Módulo 11
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += ((int) $ruc[$i]) * self::FACTORS[$i];
        }

        $remainder = $sum % 11;
        $checkDigit = 11 - $remainder;

        if ($checkDigit === 10) {
            $checkDigit = 0;
        } elseif ($checkDigit === 11) {
            $checkDigit = 1;
        }

        return $checkDigit === (int) $ruc[10];
    }
}
