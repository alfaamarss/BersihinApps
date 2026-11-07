<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function ok(mixed $data = null, string|array $metaOrMessage = []): JsonResponse
    {
        // If second parameter is string, treat it as message
        $meta = is_string($metaOrMessage) ? ['message' => $metaOrMessage] : $metaOrMessage;
        
        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => (object) $meta,
            'errors' => [],
        ]);
    }

    protected function fail(string $message, int $status = 400, array $errors = [], array $meta = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'meta' => (object) $meta,
            'errors' => [
                'message' => $message,
                'details' => $errors,
            ],
        ], $status);
    }

    protected function okPaginated($paginator): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
            'errors' => [],
        ]);
    }

    protected function created(mixed $data = null, string $message = 'Resource created successfully'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'message' => $message,
            ],
            'errors' => [],
        ], 201);
    }

    protected function error(string $message, int $status = 400, array $errors = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'meta' => [],
            'errors' => [
                'message' => $message,
                'details' => $errors,
            ],
        ], $status);
    }
}
