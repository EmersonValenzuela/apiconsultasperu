<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Histórico de tipo de cambio (docs/04-base-de-datos.md).
 * Portable SQLite / PostgreSQL.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('cotizaciones_moneda', function (Blueprint $table) {
            $table->id();
            $table->char('moneda', 3);
            $table->decimal('compra', 10, 4)->nullable();
            $table->decimal('venta', 10, 4);
            $table->string('tipo', 20)->default('referencial');
            $table->string('fuente', 60);
            $table->date('fecha');
            $table->timestampTz('obtenido_en');
            $table->timestampsTz();

            $table->unique(['moneda', 'fecha', 'fuente']);
            $table->index(['moneda', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizaciones_moneda');
    }
};
