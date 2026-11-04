<?php

use App\Modules\Dni\Services\DniService;
use App\Modules\Dni\Services\TurnstileVerifier;

test('valida formato de dni de 8 digitos correctamente', function () {
    $service = new DniService(new TurnstileVerifier());

    $valido = $service->validarFormato('72345678');
    expect($valido['valido'])->toBeTrue()
        ->and($valido['dni'])->toBe('72345678')
        ->and($valido['formato'])->toBe('DNI estándar (8 dígitos)');

    $invalidoCorto = $service->validarFormato('1234567'); // 7 dígitos
    expect($invalidoCorto['valido'])->toBeFalse();

    $invalidoLargo = $service->validarFormato('123456789'); // 9 dígitos
    expect($invalidoLargo['valido'])->toBeFalse();

    $invalidoLetras = $service->validarFormato('1234567a');
    expect($invalidoLetras['valido'])->toBeFalse();

    $invalidoVacio = $service->validarFormato('');
    expect($invalidoVacio['valido'])->toBeFalse();
});
