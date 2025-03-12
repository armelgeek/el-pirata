<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Http\Responses\ApiResponse;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, ...$permissions)
    {
        if (!$request->user()) {
            return ApiResponse::error('Non authentifié', Response::HTTP_UNAUTHORIZED);
        }

        // Vérifier si l'utilisateur a au moins une des permissions requises
        foreach ($permissions as $permission) {
            if ($request->user()->hasPermission($permission)) {
                return $next($request);
            }
        }

        return ApiResponse::error('Accès non autorisé', Response::HTTP_FORBIDDEN);
    }
}
