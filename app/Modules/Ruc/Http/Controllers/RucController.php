<?php

namespace App\Modules\Ruc\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Ruc\Services\RucService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RucController extends Controller
{
    /**
     * Consulta de RUC con validación de dígito verificador y failover de fuentes.
     */
    public function show(string $ruc, Request $request, RucService $service): JsonResponse
    {
        $result = $service->consultar($ruc, $request->ip());

        if ($result === null) {
            return ApiResponse::error(
                ApiResponse::ERROR_NOT_FOUND,
                'El RUC solicitado no fue encontrado en el padrón de contribuyentes.',
                404
            );
        }

        $response = ApiResponse::fromSource(
            $result['data'],
            $result['meta']['actualizado_en'],
            $result['meta']['fuente'],
            $result['meta']['stale']
        );

        $response->header('Cache-Control', 'public, max-age=604800, stale-while-revalidate=1209600');

        $etag = '"'.md5((string) $response->getContent()).'"';
        $response->setEtag($etag);

        if ($request->header('If-None-Match') === $etag) {
            $response->setStatusCode(304);
            $response->setContent('');
        }

        return $response;
    }
}
