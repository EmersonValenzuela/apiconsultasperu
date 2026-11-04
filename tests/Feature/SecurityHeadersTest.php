<?php

test('las respuestas de la api incluyen las cabeceras de seguridad requeridas', function () {
    $response = $this->getJson('/api/v1/health');

    $response->assertStatus(200)
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'no-referrer')
        ->assertHeader('X-Frame-Options', 'DENY');
});

test('las respuestas bajo https incluyen Strict-Transport-Security', function () {
    $response = $this->getJson('https://localhost/api/v1/health');

    $response->assertStatus(200)
        ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});
