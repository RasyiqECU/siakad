<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Helper bungkus response agar konsisten dengan format API_SPECIFICATION §1:
 * - Success tunggal : { success, message, data }
 * - Success list    : { success, message, data, meta, links }
 * - Error           : { success:false, message } (+ errors utk 422 via renderer)
 */
trait RespondsWithEnvelope
{
    protected function respondSingle(mixed $data, string $message = 'Berhasil.', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    protected function respondDeleted(string $message = 'Data berhasil dihapus.'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => null,
        ], 200);
    }

    protected function respondError(string $message, int $status = 400, mixed $data = null, array $errors = []): JsonResponse
    {
        $body = [
            'success' => false,
            'message' => $message,
        ];

        if ($data !== null) {
            $body['data'] = $data;
        }

        if ($errors !== []) {
            $body['errors'] = $errors;
        }

        return response()->json($body, $status);
    }

    protected function respondPaginated(LengthAwarePaginator $paginator, string $resourceClass, string $message = 'Data berhasil dimuat.'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $resourceClass::collection(collect($paginator->items()))->toArray(request()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ]);
    }
}
