<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Salud de cada integración externa, usada por GET /api/v1/health (docs/04).
 * Portable SQLite / PostgreSQL.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('fuentes_estado', function (Blueprint $table) {
            $table->string('modulo', 30)->primary();
            $table->timestampTz('ultima_ok')->nullable();
            $table->timestampTz('ultimo_error')->nullable();
            $table->text('mensaje_error')->nullable();
            $table->integer('intentos_fallidos')->default(0);
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuentes_estado');
    }
};
