<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(mixed $data = null, array $meta = [], ?string $message = null, int $status = 200): JsonResponse
    {
        return response()->api($data, $status, $meta, $message);
    }

    public static function error(?string $message = null, int $status = 400, array $meta = [], mixed $data = null): JsonResponse
    {
        return response()->api($data, $status, $meta, $message);
    }
}
