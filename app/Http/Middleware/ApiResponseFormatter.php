<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Http\Responses\ApiResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiResponseFormatter
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Si la réponse est déjà une instance de JsonResponse, on la retourne telle quelle
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            return $response;
        }

        // Récupérer le contenu de la réponse
        $content = $response->getContent();
        $statusCode = $response->getStatusCode();

        // Si c'est une erreur
        if ($statusCode >= 400) {
            return ApiResponse::error(
                $content,
                $statusCode
            );
        }

        // Décoder le contenu JSON si possible
        $data = json_decode($content, true);
        
        // Si le contenu n'est pas du JSON valide, utiliser le contenu brut
        if (json_last_error() !== JSON_ERROR_NONE) {
            $data = $content;
        }

        // Vérifier si c'est une réponse paginée
        if (is_array($data) && isset($data['data']) && isset($data['total'])) {
            return ApiResponse::paginated(
                $data['data'],
                $data['total'],
                $data['current_page'],
                $data['per_page']
            );
        }

        // Réponse standard
        return ApiResponse::success($data);
    }
}
