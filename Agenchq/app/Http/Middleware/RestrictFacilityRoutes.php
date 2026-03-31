<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RestrictFacilityRoutes
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || ($user->role ?? null) !== 'facility') {
            return $next($request);
        }

        $path = '/' . ltrim((string) $request->path(), '/');

        $allowed = [
            '/api/logout',
            '/api/user',
        ];

        if (in_array($path, $allowed, true)) {
            return $next($request);
        }

        if (str_starts_with($path, '/api/v1/facility/')) {
            return $next($request);
        }

        if ($path === '/api/v1/system/banner') {
            return $next($request);
        }

        Log::warning('Facility route access blocked', [
            'user_id' => $user->id,
            'path' => $path,
            'method' => $request->method(),
        ]);

        return response()->json(['message' => 'Unauthorized. Facility users may only access facility endpoints.'], 403);
    }
}
