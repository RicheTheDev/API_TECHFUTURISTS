<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponseTrait
{
    protected function successResponse($data = null, string $message = 'Opération réussie', int $status = 200): JsonResponse
    {
        return response()->json([
            'status'  => $status,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    protected function createdResponse($data = null, string $message = 'Opération accomplie', int $status = 201): JsonResponse
    {
        return response()->json([
            'status'  => $status,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    protected function deletedResponse(string $message = 'Opération accomplie', int $status = 204): JsonResponse
    {
        return response()->json([
            'status'  => $status,
            'message' => $message,
        ], $status);
    }

    protected function updatedResponse($data = null, string $message = 'Opération accomplie', int $status = 200): JsonResponse
    {
        return response()->json([
            'status'  => $status,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }
    protected function validationErrorResponse($errors, string $message = 'La validation a échoué.', int $status = 422): JsonResponse
    {
        return response()->json([
            'status'  => $status,
            'message' => $message,
            'errors'  => $errors,
        ], $status);
    }

    protected function unauthorizedResponse(string $message = 'Non autorisé', int $status = 401): JsonResponse
    {
        return response()->json([
            'status'  => $status,
            'message' => $message,
        ], $status);
    }

    protected function forbiddenResponse(string $message = 'Accès refusé', int $status = 403): JsonResponse
    {
        return response()->json([
            'status'  => $status,
            'message' => $message,
        ], $status);
    }

    protected function notFoundResponse(string $message = 'Ressource introuvable', int $status = 404): JsonResponse
    {
        return response()->json([
            'status'  => $status,
            'message' => $message,
        ], $status);
    }

    protected function serverErrorResponse(string $message = 'Erreur serveur', $error = null, int $status = 500): JsonResponse
    {
        return response()->json([
            'status'  => $status,
            'message' => $message,
            'error'   => $error,
        ], $status);
    }
}
