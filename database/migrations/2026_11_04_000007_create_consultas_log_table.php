<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de auditoría para consultas de RUC y DNI sin datos personales (docs/04-base-de-datos.md).
 * Portable SQLite / PostgreSQL.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('consultas_log', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 10); // ruc, dni
            $table->string('documento_hash', 64);
            $table->string('ip_hash', 64);
            $table->string('resultado', 20); // ok, no_encontrado, error, limitado
            $table->timestampTz('creado_en');

            $table->index('creado_en');
            $table->index(['tipo', 'creado_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultas_log');
    }
};
