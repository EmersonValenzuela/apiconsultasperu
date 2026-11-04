<?php

namespace App\Modules\Dolar\Commands;

use App\Modules\Dolar\Services\DolarService;
use Illuminate\Console\Command;
use Throwable;

class SyncExchangeRatesCommand extends Command
{
    protected $signature = 'dolar:sincronizar';

    protected $description = 'Sincroniza las cotizaciones del dólar y monedas desde la fuente externa';

    public function handle(DolarService $service): int
    {
        $this->info('Sincronizando cotizaciones del dólar...');

        try {
            $result = $service->syncRates();
            $this->info("Cotización actualizada: USD/PEN = {$result['data']['venta']} (fecha: {$result['data']['fecha']})");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error("Error al sincronizar cotizaciones: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
