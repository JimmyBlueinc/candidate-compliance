<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationDomain;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class TenantOnboardingController extends Controller
{
    /**
     * Get onboarding status.
     * 
     * CRITICAL: logo_path now contains the FULL CloudFront URL.
     * No Storage access during read - just return what's in the DB.
     */
    public function status(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $org = Organization::query()->findOrFail($orgId);

        // logo_path is now stored as full URL: https://cdn.agenchq.com/branding/org-123/logo_xxx.png
        $logoUrl = null;
        if ($org->logo_path) {
            $logoUrl = $org->logo_path;
            $version = $org->updated_at ? $org->updated_at->timestamp : time();
            $separator = str_contains($logoUrl, '?') ? '&' : '?';
            $logoUrl .= $separator . 'v=' . $version;
        }

        return response()->api([
            'organization' => [
                'id' => $org->id,
                'name' => $org->name,
                'slug' => $org->slug,
                'subdomain' => $org->subdomain,
                'primary_color' => $org->primary_color,
                'logo_url' => $logoUrl,
                'onboarding_step' => $org->onboarding_step,
                'onboarding_completed_at' => $org->onboarding_completed_at,
            ],
        ])->header('Cache-Control', 'no-store');
    }

    public function checkSubdomain(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subdomain' => ['required', 'string', 'max:50'],
        ]);

        $subdomain = strtolower(trim($validated['subdomain']));

        $reserved = ['www', 'app', 'api', 'admin', 'portal', 'candidate', 'facility', 'platform', 'support', 'help', 'status', 'mail', 'smtp'];

        $formatOk = (bool) preg_match('/^[a-z0-9](?:[a-z0-9-]{1,48})[a-z0-9]$/', $subdomain);
        $reservedOk = !in_array($subdomain, $reserved, true);

        $available = $formatOk
            && $reservedOk
            && !Organization::query()->where('subdomain', $subdomain)->exists();

        return response()->api([
            'subdomain' => $subdomain,
            'available' => $available,
            'format_ok' => $formatOk,
            'reserved' => !$reservedOk,
        ]);
    }

    public function setSubdomain(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $validated = $request->validate([
            'subdomain' => ['required', 'string', 'max:50'],
        ]);

        $subdomain = strtolower(trim($validated['subdomain']));
        $reserved = ['www', 'app', 'api', 'admin', 'portal', 'candidate', 'facility', 'platform', 'support', 'help', 'status', 'mail', 'smtp'];

        if (!preg_match('/^[a-z0-9](?:[a-z0-9-]{1,48})[a-z0-9]$/', $subdomain)) {
            return response()->json([
                'message' => 'Invalid subdomain format.',
            ], 422);
        }

        if (in_array($subdomain, $reserved, true)) {
            return response()->json([
                'message' => 'This subdomain is reserved.',
            ], 422);
        }

        $org = Organization::query()->findOrFail($orgId);

        $exists = Organization::query()
            ->where('subdomain', $subdomain)
            ->where('id', '!=', $orgId)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'This subdomain is already taken.',
            ], 422);
        }

        $org->subdomain = $subdomain;
        $org->onboarding_step = 'branding';
        $org->save();

        $tenantBaseDomain = env('TENANT_BASE_DOMAIN');
        if ($tenantBaseDomain) {
            $fullDomain = $subdomain . '.' . ltrim($tenantBaseDomain, '.');

            OrganizationDomain::firstOrCreate([
                'domain' => strtolower($fullDomain),
            ], [
                'organization_id' => $orgId,
                'is_active' => true,
            ]);
        }

        return response()->api([
            'subdomain' => $org->subdomain,
            'onboarding_step' => $org->onboarding_step,
        ], 200, [], 'Subdomain saved.');
    }

    /**
     * Update branding during onboarding.
     * 
     * CRITICAL: Logo uploads go to 'public_assets' disk (S3 + CloudFront).
     * The FULL CloudFront URL is stored in logo_path column.
     * No relative paths, no S3 reads on retrieval.
     */
    public function branding(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $validated = $request->validate([
            'primary_color' => ['nullable', 'string', 'max:20'],
            'logo' => ['sometimes', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:4096'],
        ]);

        $org = Organization::query()->findOrFail($orgId);

        // Always update primary_color if provided in validated data
        if (array_key_exists('primary_color', $validated)) {
            $org->primary_color = $validated['primary_color'];
            Log::info('[ONBOARDING BRANDING] Updating primary_color', ['org_id' => $orgId, 'new_color' => $validated['primary_color']]);
        }

        if ($request->hasFile('logo')) {
            // Generate unique filename to bust browser cache
            $file = $request->file('logo');
            $extension = $file->getClientOriginalExtension();
            $filename = 'logo_' . time() . '_' . uniqid() . '.' . $extension;
            
            // Store in public_assets disk (S3 + CloudFront)
            // Path structure: branding/org-{id}/logo_timestamp_unique.ext
            $path = 'branding/org-' . $orgId . '/' . $filename;
            Storage::disk('public_assets')->put($path, file_get_contents($file));
            
            // Get the FULL CloudFront URL and store it in the database
            $logoUrl = Storage::disk('public_assets')->url($path);
            $org->logo_path = $logoUrl; // Store FULL URL, not path
            
            Log::info('[ONBOARDING BRANDING] Logo uploaded', [
                'org_id' => $orgId, 
                'path' => $path,
                'url' => $logoUrl,
            ]);
        }

        $org->onboarding_step = 'complete';
        $org->save();

        // Build response URL with cache-busting param
        $responseLogoUrl = null;
        if ($org->logo_path) {
            // logo_path already contains full URL
            $responseLogoUrl = $org->logo_path;
            $version = $org->updated_at ? $org->updated_at->timestamp : time();
            $separator = str_contains($responseLogoUrl, '?') ? '&' : '?';
            $responseLogoUrl .= $separator . 'v=' . $version;
        }

        return response()->api([
            'tenant_id' => $org->id,
            'name' => $org->name,
            'slug' => $org->slug,
            'subdomain' => $org->subdomain,
            'primary_color' => $org->primary_color,
            'logo_url' => $responseLogoUrl,
            'onboarding_step' => $org->onboarding_step,
            'onboarding_completed_at' => $org->onboarding_completed_at,
        ], 200, [], 'Branding updated.')->header('Cache-Control', 'no-store');
    }

    public function complete(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $org = Organization::query()->findOrFail($orgId);

        if (!$org->subdomain) {
            return response()->json([
                'message' => 'Please choose a subdomain before completing onboarding.',
            ], 422);
        }

        $org->onboarding_completed_at = now();
        $org->onboarding_step = 'done';
        $org->save();

        // Build subdomain URL for redirect
        $subdomainUrl = $this->buildSubdomainUrl($org->subdomain);

        return response()->api([
            'onboarding_completed_at' => $org->onboarding_completed_at,
            'onboarding_step' => $org->onboarding_step,
            'subdomain' => $org->subdomain,
            'redirect_url' => $subdomainUrl,
        ], 200, [], 'Onboarding completed.');
    }
    
    private function buildSubdomainUrl(string $subdomain): string
    {
        $scheme = request()->secure() ? 'https' : 'http';
        return "{$scheme}://{$subdomain}.agenchq.com";
    }
}
