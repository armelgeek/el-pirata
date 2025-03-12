<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiResponse
{
    /**
     * Format de réponse standard pour les succès
     */
    public static function success(
        $data = null,
        string $message = '',
        int $statusCode = Response::HTTP_OK,
        ?int $totalRecords = null,
        ?int $currentPage = null,
        ?int $pageSize = null,
        ?int $totalPages = null
    ): JsonResponse {
        $response = [
            'message' => $message,
            'isError' => false,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        // Ajouter les informations de pagination si présentes
        if ($totalRecords !== null) {
            $response['totalRecords'] = $totalRecords;
            $response['currentPage'] = $currentPage;
            $response['pageSize'] = $pageSize;
            $response['totalPages'] = $totalPages;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Format de réponse standard pour les erreurs
     */
    public static function error(
        string $message = "Une erreur s'est produite",
        int $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR,
        ?string $errorCode = null,
        $details = null
    ): JsonResponse {
        $response = [
            'message' => $message,
            'isError' => true,
            'errorCode' => $errorCode ?? 'ERR_' . $statusCode,
        ];

        if ($details !== null) {
            $response['details'] = $details;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Format de réponse pour les requêtes invalides
     */
    public static function badRequest(
        $details = null,
        string $message = "Requête invalide"
    ): JsonResponse {
        return self::error(
            $message,
            Response::HTTP_BAD_REQUEST,
            'ERR_BAD_REQUEST',
            $details
        );
    }

    /**
     * Format de réponse pour les réponses paginées
     */
    public static function paginated(
        $data,
        int $total,
        int $currentPage,
        int $perPage,
        string $message = ''
    ): JsonResponse {
        return self::success(
            $data,
            $message,
            Response::HTTP_OK,
            $total,
            $currentPage,
            $perPage,
            ceil($total / $perPage)
        );
    }
}
