<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json(['message' => 'Utilisateur non trouvé'], 401);
            }

            // **Injecte l'utilisateur dans le guard par défaut de Laravel**
            auth()->setUser($user);

        } catch (TokenExpiredException $e) {
            return response()->json(['message' => 'Token expiré'], 401);
        } catch (TokenInvalidException $e) {
            return response()->json(['message' => 'Token invalide'], 401);
        } catch (JWTException $e) {
            return response()->json(['message' => 'Token manquant'], 401);
        }

        return $next($request);
    }
}
