<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowedOrigins = explode(',', env('CORS_ALLOWED_ORIGINS', '*'));
        $origin = $request->headers->get('Origin');

        $headers = [
            'Access-Control-Allow-Methods'     => env('CORS_ALLOWED_METHODS', 'GET,POST,PUT,PATCH,DELETE,OPTIONS'),
            'Access-Control-Allow-Headers'     => env('CORS_ALLOWED_HEADERS', 'Content-Type, X-Requested-With, Authorization, X-CSRF-TOKEN'),
            'Access-Control-Allow-Credentials' => env('CORS_SUPPORTS_CREDENTIALS', 'true'),
        ];

        // ✅ Autoriser seulement les origines listées
        if (in_array($origin, $allowedOrigins)) {
            $headers['Access-Control-Allow-Origin'] = $origin;
        }

        // Réponse aux requêtes OPTIONS (preflight)
        if ($request->getMethod() === 'OPTIONS') {
            return response()->json('OK', 200, $headers);
        }

        $response = $next($request);

        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }
}

