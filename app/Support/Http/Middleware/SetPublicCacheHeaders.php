<?php

namespace App\Support\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Agrega cabeceras Cache-Control públicas y ETag a endpoints GET exitosos.
 * Soporta respuestas 304 Not Modified cuando coincide If-None-Match.
 */
class SetPublicCacheHeaders
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, int $maxAge = 1800): Response
    {
        $response = $next($request);

        if ($request->isMethod('GET') && $response->isSuccessful()) {
            $content = $response->getContent();
            $etag = '"' . md5((string) $content) . '"';
            $staleWhileRevalidate = $maxAge * 2;

            $response->headers->set('Cache-Control', "public, max-age={$maxAge}, stale-while-revalidate={$staleWhileRevalidate}");
            $response->headers->set('ETag', $etag);

            if ($request->headers->get('If-None-Match') === $etag) {
                $response->setNotModified();
            }
        }

        return $response;
    }
}
