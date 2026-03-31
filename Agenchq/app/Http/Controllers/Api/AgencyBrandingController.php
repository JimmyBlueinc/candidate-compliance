<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class AgencyBrandingController extends Controller
{
    /**
     * Update agency branding.
     * 
     * CRITICAL: Logo uploads go to 'public_assets' disk (S3 + CloudFront).
     * The FULL CloudFront URL is stored in logo_path column.
     * No relative paths, no S3 reads on retrieval.
     */
    public function update(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        // Debug: log raw request info
        Log::info('[BRANDING] Request debug', [
            'org_id' => $orgId,
            'has_file_logo' => $request->hasFile('logo'),
            'file_logo' => $request->file('logo') ? 'present' : 'null',
            'all_files' => array_keys($request->allFiles()),
            'all_input_keys' => array_keys($request->all()),
            'content_type' => $request->header('Content-Type'),
        ]);

        $validated = $request->validate([
            'primary_color' => ['nullable', 'string', 'max:20'],
            'logo' => ['sometimes', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:4096'],
        ]);

        $org = Organization::query()->findOrFail($orgId);

        // Always update primary_color if provided in validated data
        if (array_key_exists('primary_color', $validated)) {
            $org->primary_color = $validated['primary_color'];
            Log::info('[BRANDING] Updating primary_color', ['org_id' => $orgId, 'new_color' => $validated['primary_color']]);
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
            
            Log::info('[BRANDING] Logo uploaded', [
                'org_id' => $orgId, 
                'path' => $path,
                'url' => $logoUrl,
            ]);
        }

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

        Log::info('[BRANDING] Response', [
            'org_id' => $orgId,
            'primary_color' => $org->primary_color,
            'logo_url' => $responseLogoUrl,
        ]);

        return response()->api([
            'tenant_id' => $org->id,
            'name' => $org->name,
            'slug' => $org->slug,
            'primary_color' => $org->primary_color,
            'logo_url' => $responseLogoUrl,
        ], 200, [], 'Branding updated.')->header('Cache-Control', 'no-store');
    }
}
