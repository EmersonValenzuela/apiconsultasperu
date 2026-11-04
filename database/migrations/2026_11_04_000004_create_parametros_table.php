<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de parámetros legales y tributarios (docs/04-base-de-datos.md).
 * Portable SQLite / PostgreSQL.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('parametros', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 60);
            $table->decimal('valor', 14, 4);
            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable();
            $table->string('fuente_legal', 255);
            $table->date('verificado_en');
            $table->timestampsTz();

            $table->index(['clave', 'vigente_desde']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parametros');
    }
};
