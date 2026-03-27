<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanManageCandidates
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !in_array($user->role, ['platform_admin', 'org_super_admin', 'admin', 'recruiter', 'compliance', 'scheduler', 'finance', 'logistics'], true)) {
            return response()->json([
                'message' => 'Unauthorized. Candidate management access required.',
            ], 403);
        }

        return $next($request);
    }
}
