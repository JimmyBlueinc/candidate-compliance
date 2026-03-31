<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsTravel
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !in_array($request->user()->role, ['travel', 'admin', 'org_super_admin', 'platform_admin'], true)) {
            return response()->json([
                'message' => 'Unauthorized. Travel access required.',
            ], 403);
        }

        return $next($request);
    }
}
