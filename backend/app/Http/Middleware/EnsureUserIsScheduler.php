<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsScheduler
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !in_array((string) $user->role, ['scheduler', 'org_super_admin'], true)) {
            return response()->json([
                'message' => 'Unauthorized. Scheduler access required.',
            ], 403);
        }

        return $next($request);
    }
}
