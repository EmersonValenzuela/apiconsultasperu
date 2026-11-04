<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de feriados y días no laborables (docs/04-base-de-datos.md).
 * Portable SQLite / PostgreSQL.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('feriados', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('nombre', 150);
            $table->string('tipo', 30)->default('feriado'); // feriado, dia_no_laborable, feriado_puente
            $table->smallInteger('anio');
            $table->string('fuente', 60); // nager, manual
            $table->timestampsTz();

            $table->unique(['fecha', 'nombre']);
            $table->index('anio');
            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feriados');
    }
};
