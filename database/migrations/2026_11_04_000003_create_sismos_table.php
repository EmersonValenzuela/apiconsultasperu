<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de sismos (docs/04-base-de-datos.md).
 * Portable SQLite / PostgreSQL.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('sismos', function (Blueprint $table) {
            $table->id();
            $table->string('id_externo', 80)->unique();
            $table->decimal('magnitud', 3, 1);
            $table->string('lugar', 255);
            $table->decimal('profundidad_km', 6, 1)->nullable();
            $table->decimal('latitud', 9, 6);
            $table->decimal('longitud', 9, 6);
            $table->timestampTz('ocurrido_en');
            $table->string('fuente', 40);
            $table->string('url', 255)->nullable();
            $table->json('crudo')->nullable();
            $table->timestampsTz();

            $table->index('ocurrido_en');
            $table->index('magnitud');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sismos');
    }
};
