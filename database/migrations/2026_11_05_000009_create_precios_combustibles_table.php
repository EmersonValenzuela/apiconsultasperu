<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('precios_combustibles', function (Blueprint $table) {
            $table->id();
            $table->string('region', 60)->default('Lima y Callao')->index();
            $table->string('tipo', 40)->index();
            $table->string('nombre', 100);
            $table->string('octanaje', 50)->nullable();
            $table->string('unidad', 30);
            $table->string('simbolo_unidad', 10);
            $table->decimal('precio_promedio', 8, 2);
            $table->decimal('precio_minimo', 8, 2);
            $table->decimal('precio_maximo', 8, 2);
            $table->string('fuente', 100)->default('Osinergmin / Facilito / Petroperú');
            $table->date('vigencia_desde');
            $table->timestamps();

            $table->unique(['region', 'tipo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('precios_combustibles');
    }
};
