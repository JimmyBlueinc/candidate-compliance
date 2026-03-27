<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Models\OrganizationDomain;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class ResolveOrganization
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('api/login') || $request->is('api/register') || $request->is('api/forgot-password') || $request->is('api/reset-password') || $request->is('api/super-admin/create') || $request->is('api/super-admin/set-password') || $request->is('api/private/platform-admin/*') || $request->is('api/health') || $request->is('api/health/*') || $request->is('api/brand') || $request->is('api/brand/*') || $request->is('api/test-minimal')) {
            return $next($request);
        }

        if ($request->is('api/public/*')) {
            return $next($request);
        }

        if ($request->is('api/platform/*') || $request->is('api/admin/users')) {
            return $next($request);
        }

        $sanctumUser = Auth::guard('sanctum')->user();
        if ($sanctumUser && ($sanctumUser->role ?? null) === 'platform_admin') {
            return $next($request);
        }

        $bearerToken = $request->bearerToken();
        if ($bearerToken) {
            $accessToken = PersonalAccessToken::findToken($bearerToken);
            $tokenUser = $accessToken?->tokenable;
            if ($tokenUser && ($tokenUser->role ?? null) === 'platform_admin') {
                return $next($request);
            }

            if ($tokenUser && ($tokenUser->organization_id ?? null)) {
                $org = Organization::query()
                    ->whereKey((int) $tokenUser->organization_id)
                    ->where('is_active', true)
                    ->first();

                if ($org) {
                    $request->attributes->set('organization', $org);
                    $request->attributes->set('organization_id', $org->id);
                    return $next($request);
                }
            }
        }

        $tenantId = (int) ($request->header('X-Tenant-Id') ?: 0);
        if ($tenantId > 0) {
            $org = Organization::query()->whereKey($tenantId)->where('is_active', true)->first();
            
            // DEBUG: Log tenant resolution via header
            \Log::info('[RESOLVE_ORG] X-Tenant-Id header resolution', [
                'tenant_id_header' => $tenantId,
                'org_found' => $org ? $org->id : null,
                'org_active' => $org ? $org->is_active : null,
                'request_path' => $request->path(),
            ]);
            
            if (!$org) {
                \Log::warning('[RESOLVE_ORG] Organization not found or inactive for X-Tenant-Id', [
                    'tenant_id' => $tenantId,
                ]);
                return response()->json([
                    'message' => 'Organization not authorized for this tenant id.',
                ], 403);
            }

            $request->attributes->set('organization', $org);
            $request->attributes->set('organization_id', $org->id);

            $user = $request->user();
            if ($user && $user->organization_id && (int) $user->organization_id !== (int) $org->id) {
                \Log::warning('[RESOLVE_ORG] User-org mismatch via X-Tenant-Id', [
                    'user_id' => $user->id,
                    'user_org_id' => $user->organization_id,
                    'target_org_id' => $org->id,
                    'tenant_id_header' => $tenantId,
                ]);
                return response()->json([
                    'message' => 'User does not belong to this organization.',
                ], 403);
            }
            
            \Log::info('[RESOLVE_ORG] X-Tenant-Id resolution SUCCESS', [
                'org_id' => $org->id,
                'user_id' => $user ? $user->id : null,
                'user_org_id' => $user ? $user->organization_id : null,
            ]);

            return $next($request);
        }

        $user = $request->user();

        if ($user && $user->role === 'platform_admin') {
            return $next($request);
        }

        $host = $request->header('X-Org-Host');
        if (!$host) {
            $host = $request->header('Origin');
            if ($host) {
                $host = parse_url($host, PHP_URL_HOST);
            }
        }

        // DEBUG: Log host resolution attempt
        \Log::info('[RESOLVE_ORG] Host-based resolution attempt', [
            'x_org_host' => $request->header('X-Org-Host'),
            'origin' => $request->header('Origin'),
            'resolved_host' => $host,
            'request_path' => $request->path(),
            'user_id' => $user ? $user->id : null,
            'user_org_id' => $user ? $user->organization_id : null,
        ]);

        if (!$host) {
            \Log::warning('[RESOLVE_ORG] No host context available');
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $host = strtolower(trim($host));
        $host = preg_replace('#:\\d+$#', '', $host);

        $domain = OrganizationDomain::query()
            ->where('domain', $host)
            ->where('is_active', true)
            ->with('organization')
            ->first();

        if (!$domain || !$domain->organization) {
            $defaultOrg = Organization::query()->where('slug', 'default')->where('is_active', true)->first();
            if ($defaultOrg) {
                if (app()->environment('local') && in_array($host, ['localhost', '127.0.0.1'], true)) {
                    $request->attributes->set('organization', $defaultOrg);
                    $request->attributes->set('organization_id', $defaultOrg->id);
                    return $next($request);
                }

                $appUrlHost = parse_url(config('app.url'), PHP_URL_HOST);
                if ($appUrlHost && strtolower((string) $appUrlHost) === $host) {
                    $request->attributes->set('organization', $defaultOrg);
                    $request->attributes->set('organization_id', $defaultOrg->id);
                    return $next($request);
                }
            }
        }

        if (!$domain || !$domain->organization || !$domain->organization->is_active) {
            return response()->json([
                'message' => 'Organization not authorized for this domain.',
            ], 403);
        }

        $org = $domain->organization;

        $request->attributes->set('organization', $org);
        $request->attributes->set('organization_id', $org->id);

        if ($user && $user->organization_id && (int) $user->organization_id !== (int) $org->id) {
            return response()->json([
                'message' => 'User does not belong to this organization.',
            ], 403);
        }

        return $next($request);
    }
}
