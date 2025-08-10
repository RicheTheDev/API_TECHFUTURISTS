<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Enums\RoleEnum;
use ValueError;

class RoleMiddleware
{
    /**
     * Vérifie que l'utilisateur a le rôle requis.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role  Le rôle attendu (doit correspondre à un RoleEnum)
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = auth()->user();

        try {
            $expectedRole = RoleEnum::from($role)->value;
        } catch (ValueError) {
            return response()->json([
                'status'  => 400,
                'message' => "Rôle '$role' invalide. Valeurs autorisées : " . implode(', ', RoleEnum::getValues())
            ], 400);
        }
        if (!$user || $user->role->value !== $expectedRole) {
            return response()->json([
                'status'  => 403,
                'message' => "Accès interdit pour le rôle : " . ($user?->role->value ?? 'inconnu')
            ], 403);
        }

        return $next($request);
    }
}
