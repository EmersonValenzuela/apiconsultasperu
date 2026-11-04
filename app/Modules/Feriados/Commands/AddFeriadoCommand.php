<?php

namespace App\Modules\Feriados\Commands;

use App\Modules\Feriados\Models\Feriado;
use App\Modules\Feriados\Services\FeriadosService;
use Illuminate\Console\Command;

class AddFeriadoCommand extends Command
{
    protected $signature = 'feriados:agregar 
                            {--fecha= : Fecha en formato YYYY-MM-DD}
                            {--nombre= : Nombre del feriado o día no laborable}
                            {--tipo=feriado : Tipo (feriado, dia_no_laborable, feriado_puente)}';

    protected $description = 'Agrega o actualiza manualmente un feriado o día no laborable oficial';

    public function handle(FeriadosService $service): int
    {
        $fecha = (string) $this->option('fecha');
        $nombre = (string) $this->option('nombre');
        $tipo = (string) $this->option('tipo');

        if (empty($fecha) || empty($nombre)) {
            $this->error('Los parámetros --fecha y --nombre son obligatorios.');

            return self::FAILURE;
        }

        $validTipos = [Feriado::TIPO_FERIADO, Feriado::TIPO_DIA_NO_LABORABLE, Feriado::TIPO_FERIADO_PUENTE];
        if (! in_array($tipo, $validTipos, true)) {
            $this->error('Tipo inválido. Opciones válidas: ' . implode(', ', $validTipos));

            return self::FAILURE;
        }

        $feriado = $service->addFeriado($fecha, $nombre, $tipo);
        $this->info("Feriado registrado con éxito: [{$feriado->fecha->format('Y-m-d')}] {$feriado->nombre} ({$feriado->tipo}) [fuente: {$feriado->fuente}]");

        return self::SUCCESS;
    }
}
