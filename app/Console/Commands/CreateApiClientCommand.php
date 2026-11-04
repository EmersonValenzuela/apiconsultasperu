<?php

namespace App\Console\Commands;

use App\Models\ClienteApi;
use Illuminate\Console\Command;

class CreateApiClientCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clientes:crear 
                            {nombre : Nombre descriptivo del cliente (ej. App Android)} 
                            {--limite=120 : Límite de peticiones por minuto}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea un nuevo cliente API y genera su clave de acceso (X-App-Key)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $nombre = trim((string) $this->argument('nombre'));
        $limite = (int) $this->option('limite');

        if ($nombre === '') {
            $this->error('El nombre del cliente no puede estar vacío.');

            return Command::FAILURE;
        }

        if ($limite <= 0) {
            $this->error('El límite por minuto debe ser mayor a 0.');

            return Command::FAILURE;
        }

        $rawKey = ClienteApi::generateRawKey();
        $hash = ClienteApi::hashKey($rawKey);

        $cliente = ClienteApi::query()->create([
            'nombre' => $nombre,
            'clave_hash' => $hash,
            'activo' => true,
            'limite_por_minuto' => $limite,
        ]);

        $this->newLine();
        $this->info('Cliente API creado exitosamente:');
        $this->table(
            ['ID', 'Nombre', 'Límite / min', 'Estado'],
            [[$cliente->id, $cliente->nombre, "{$cliente->limite_por_minuto} req/min", 'ACTIVO']]
        );

        $this->newLine();
        $this->warn('============================ CLAVE DE ACCESO ============================');
        $this->line("X-App-Key: <fg=green;options=bold>{$rawKey}</>");
        $this->warn('==========================================================================');
        $this->comment('IMPORTANTE: Guarda esta clave ahora. Por motivos de seguridad solo');
        $this->comment('almacenamos su hash SHA-256 y no se podrá volver a mostrar ni recuperar.');
        $this->newLine();

        return Command::SUCCESS;
    }
}
