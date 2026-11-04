<?php

use App\Modules\Ruc\Support\RucValidator;

test('valida ruc correctamente usando algoritmo modulo 11', function () {
    // RUC SUNAT: 20100070970
    expect(RucValidator::validate('20100070970'))->toBeTrue();

    // RUC BCP: 20100047218
    expect(RucValidator::validate('20100047218'))->toBeTrue();

    // RUC BBVA: 20100130204
    expect(RucValidator::validate('20100130204'))->toBeTrue();
});

test('rechaza rucs con prefijos invalidos', function () {
    // Prefijos válidos: 10, 15, 16, 17, 20
    expect(RucValidator::validate('30100070970'))->toBeFalse();
    expect(RucValidator::validate('00100070970'))->toBeFalse();
    expect(RucValidator::validate('11100070970'))->toBeFalse();
});

test('rechaza rucs con longitud distinta a 11 digitos o caracteres no numericos', function () {
    expect(RucValidator::validate('2010007097'))->toBeFalse(); // 10 dígitos
    expect(RucValidator::validate('201000709701'))->toBeFalse(); // 12 dígitos
    expect(RucValidator::validate('2010007097a'))->toBeFalse();
    expect(RucValidator::validate(''))->toBeFalse();
    expect(RucValidator::validate('           '))->toBeFalse();
});

test('rechaza rucs con digito verificador incorrecto', function () {
    // 20100070970 es válido, con otro dígito final debe fallar
    expect(RucValidator::validate('20100070971'))->toBeFalse();
    expect(RucValidator::validate('20100070979'))->toBeFalse();
    expect(RucValidator::validate('20100047210'))->toBeFalse();
});
