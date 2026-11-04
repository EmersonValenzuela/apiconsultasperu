<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de clientes autenticados para la app móvil (docs/04-base-de-datos.md).
 * Portable SQLite / PostgreSQL.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('clientes_api', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 80);
            $table->string('clave_hash', 64)->unique();
            $table->boolean('activo')->default(true);
            $table->integer('limite_por_minuto')->default(120);
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes_api');
    }
};
