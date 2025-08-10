<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SwaggerAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        $USERNAME = 'admin'; // identifiant
        $PASSWORD = 'secret'; // mot de passe

        if ($request->getUser() !== $USERNAME || $request->getPassword() !== $PASSWORD) {
            $headers = ['WWW-Authenticate' => 'Basic realm="Swagger API Docs"'];
            return response('Unauthorized', 401, $headers);
        }

        return $next($request);
    }
}
