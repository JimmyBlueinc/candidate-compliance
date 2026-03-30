<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CredentialDocumentController extends Controller
{
    public function show(Request $request, string $path)
    {
        // Validate signed query params without requiring absolute host/scheme match.
        // This avoids false 403s when traffic passes through proxies/CDN domains.
        if (!$request->hasValidSignature(false)) {
            abort(403);
        }

        if (!Storage::disk('credentials')->exists($path)) {
            abort(404);
        }

        return Storage::disk('credentials')->download($path);
    }
}
