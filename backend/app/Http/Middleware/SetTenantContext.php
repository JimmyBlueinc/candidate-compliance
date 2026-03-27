<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Support\Org;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('api/brand') || $request->is('api/health') || $request->is('api/health/*') || $request->is('api/test-minimal')) {
            return $next($request);
        }

        $user = $request->user();
        if ($user && ($user->role ?? null) === 'platform_admin') {
            return $next($request);
        }

        $orgId = Org::id($request);
        if ($orgId) {
            TenantContext::setId($orgId);
            return $next($request);
        }

        $defaultId = (int) (Organization::query()->where('slug', 'default')->value('id') ?? 0);
        TenantContext::setId($defaultId ?: null);

        return $next($request);
    }
}
