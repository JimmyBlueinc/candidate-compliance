<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CredentialDocumentController extends Controller
{
    public function show(Request $request, string $path)
    {
        $normalizedPath = ltrim($path, '/');
        $signature = (string) $request->query('signature', '');
        $expires = (int) $request->query('expires', 0);

        // New HMAC signature format (path|expires), resilient across proxy/CDN host changes.
        $hasValidHmacSignature = false;
        if ($signature !== '' && $expires > 0 && now()->timestamp <= $expires) {
            $expected = hash_hmac('sha256', $normalizedPath . '|' . $expires, (string) config('app.key'));
            $hasValidHmacSignature = hash_equals($expected, $signature);
        }

        // Backward compatibility for previously issued temporarySignedRoute links.
        $hasValidLegacySignature = $request->hasValidSignature(false);

        if (!$hasValidHmacSignature && !$hasValidLegacySignature) {
            abort(403);
        }

        if (!Storage::disk('credentials')->exists($normalizedPath)) {
            abort(404);
        }

        return Storage::disk('credentials')->download($normalizedPath);
    }
}
