<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ClienteApi extends Model
{
    use HasFactory;

    protected $table = 'clientes_api';

    protected $fillable = [
        'nombre',
        'clave_hash',
        'activo',
        'limite_por_minuto',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'limite_por_minuto' => 'integer',
    ];

    /**
     * Genera una clave aleatoria de alta entropía.
     */
    public static function generateRawKey(): string
    {
        return 'chp_live_'.Str::random(40);
    }

    /**
     * Calcula el hash SHA-256 de una clave en texto plano.
     */
    public static function hashKey(string $rawKey): string
    {
        return hash('sha256', $rawKey);
    }

    /**
     * Busca un cliente activo por su clave en texto plano, utilizando caché de corta duración.
     */
    public static function findActiveByRawKey(string $rawKey): ?self
    {
        $hash = self::hashKey($rawKey);
        $cacheKey = "cliente_api:hash:{$hash}";

        return Cache::remember($cacheKey, 300, function () use ($hash) {
            return self::query()
                ->where('clave_hash', $hash)
                ->where('activo', true)
                ->first();
        });
    }

    /**
     * Invalida la caché del cliente.
     */
    public function invalidateCache(): void
    {
        Cache::forget("cliente_api:hash:{$this->clave_hash}");
    }
}
