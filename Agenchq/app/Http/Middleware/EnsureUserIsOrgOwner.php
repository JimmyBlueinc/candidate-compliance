<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsOrgOwner
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !in_array($request->user()->role, ['org_super_admin', 'platform_admin', 'admin', 'recruiter'], true)) {
            return response()->json([
                'message' => 'Unauthorized. Organization admin access required.',
            ], 403);
        }

        return $next($request);
    }
}
