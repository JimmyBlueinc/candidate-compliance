<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class BrandController extends Controller
{
    /**
     * Get branding for the current tenant/subdomain.
     * 
     * IMPORTANT: This endpoint returns logo_url directly from the database.
     * The logo_path column now stores the FULL CloudFront URL, not a relative path.
     * NO S3/Storage access happens during read - only DB read.
     */
    public function show(Request $request): JsonResponse
    {
        // Extract subdomain from Host header
        $host = $request->header('Host', '');
        $subdomain = $this->extractSubdomain($host);
        
        Log::info('[BRAND] Host: ' . $host . ', Subdomain: ' . $subdomain);
        
        // If on main domain (agenchq.com, www.agenchq.com, or API domain)
        // try to get org from authenticated user's tenant context
        if (!$subdomain || in_array($subdomain, ['www', 'api', 'app', 'agenchq'])) {
            // Fall back to tenant context from auth or X-Tenant-ID header
            $tenantId = $request->header('X-Tenant-ID') ?? $request->user()?->organization_id;
            
            if ($tenantId) {
                $org = Organization::find($tenantId);
                if ($org) {
                    return $this->brandResponse($org);
                }
            }
            
            // Default brand for main domain
            return response()->json([
                'brand' => [
                    'tenant_id' => null,
                    'name' => 'AgencyHQ',
                    'slug' => 'agencyhq',
                    'primary_color' => '#6D28D9',
                    'logo_url' => null,
                ],
            ])->header('Cache-Control', 'no-store');
        }
        
        // Resolve organization by subdomain
        $org = Organization::where('subdomain', $subdomain)
            ->where('is_active', true)
            ->first();
        
        if (!$org) {
            Log::info('[BRAND] No org found for subdomain: ' . $subdomain);
            // Return default brand for unknown subdomain
            return response()->json([
                'brand' => [
                    'tenant_id' => null,
                    'name' => 'AgencyHQ',
                    'slug' => 'agencyhq',
                    'primary_color' => '#6D28D9',
                    'logo_url' => null,
                ],
            ])->header('Cache-Control', 'no-store');
        }
        
        return $this->brandResponse($org);
    }
    
    private function extractSubdomain(string $host): ?string
    {
        // Remove port if present
        $host = preg_replace('/:\d+$/', '', $host);
        
        // Check for agenchq.com domain
        if (!str_ends_with($host, '.agenchq.com') && $host !== 'agenchq.com') {
            // Not our domain - could be custom domain, extract first part
            $parts = explode('.', $host);
            return count($parts) > 1 ? $parts[0] : null;
        }
        
        // Extract subdomain from agenchq.com
        if ($host === 'agenchq.com' || $host === 'www.agenchq.com') {
            return null; // Main domain
        }
        
        // Extract subdomain (e.g., "blackbox" from "blackbox.agenchq.com")
        return str_replace('.agenchq.com', '', $host);
    }
    
    /**
     * Build brand response from organization.
     * 
     * CRITICAL: logo_path now contains the FULL CloudFront URL.
     * No transformations, no Storage access, no existence checks.
     * Just return what's in the DB.
     */
    private function brandResponse(Organization $org): JsonResponse
    {
        // logo_path is now stored as full URL: https://cdn.agenchq.com/branding/org-123/logo_xxx.png
        // Add cache-busting version param based on updated_at timestamp
        $logoUrl = null;
        if ($org->logo_path) {
            $logoUrl = $org->logo_path;
            // Add version param for cache busting
            $version = $org->updated_at ? $org->updated_at->timestamp : time();
            $separator = str_contains($logoUrl, '?') ? '&' : '?';
            $logoUrl .= $separator . 'v=' . $version;
        }
        
        return response()->json([
            'brand' => [
                'tenant_id' => $org->id,
                'name' => $org->name,
                'slug' => $org->slug,
                'subdomain' => $org->subdomain,
                'primary_color' => $org->primary_color,
                'logo_url' => $logoUrl,
            ],
        ])->header('Cache-Control', 'no-store');
    }

    /**
     * Serve logo image directly.
     * 
     * This endpoint is kept for backwards compatibility but redirects to the CDN URL.
     * Since logos are now stored with full CloudFront URLs, this should rarely be used.
     */
    public function logo(Request $request, Organization $organization): Response
    {
        if (!$organization->is_active) {
            abort(404);
        }

        // If logo_path is a full URL, redirect to it
        if ($organization->logo_path && str_starts_with($organization->logo_path, 'https://')) {
            return redirect($organization->logo_path);
        }

        // Fallback: no logo available
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="220" height="60" viewBox="0 0 220 60">'
            . '<rect width="220" height="60" rx="12" fill="#0f172a"/>'
            . '<text x="110" y="36" text-anchor="middle" font-family="Arial, sans-serif" font-size="14" fill="#cbd5e1">No Logo</text>'
            . '</svg>';

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }
}
