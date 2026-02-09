<?php

namespace App\Http\Middleware;

use App\Models\OrganizationDomain;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveOrganization
{
    public function handle(Request $request, Closure $next): Response
    {
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

        if (!$host) {
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
