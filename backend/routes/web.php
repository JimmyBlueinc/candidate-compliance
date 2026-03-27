<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/__routecheck', function () {
    return response('web routes ok', 200);
});

Route::get('/__spacheck', function () {
    return response()->json([
        'spa_view_exists' => view()->exists('spa'),
    ]);
});

Route::get('/__buildcheck', function () {
    $manifestPath = public_path('build/manifest.json');
    $result = [
        'manifest_exists' => is_file($manifestPath),
        'missing_files' => [],
    ];

    if (!is_file($manifestPath)) {
        return response()->json($result, 200);
    }

    $manifest = json_decode((string) file_get_contents($manifestPath), true);
    if (!is_array($manifest)) {
        $result['manifest_parse_error'] = true;
        return response()->json($result, 200);
    }

    $paths = [];
    foreach ($manifest as $entry) {
        if (!is_array($entry)) {
            continue;
        }

        if (!empty($entry['file']) && is_string($entry['file'])) {
            $paths[] = 'build/' . ltrim($entry['file'], '/');
        }

        if (!empty($entry['css']) && is_array($entry['css'])) {
            foreach ($entry['css'] as $css) {
                if (is_string($css)) {
                    $paths[] = 'build/' . ltrim($css, '/');
                }
            }
        }
    }

    $paths = array_values(array_unique($paths));
    foreach ($paths as $relPath) {
        $absPath = public_path($relPath);
        if (!is_file($absPath)) {
            $result['missing_files'][] = [
                'public_path' => '/' . str_replace('\\', '/', $relPath),
            ];
        }
    }

    $result['missing_count'] = count($result['missing_files']);
    return response()->json($result, 200);
});

// Serve storage files - must come BEFORE SPA fallback
Route::get('/storage/{path}', function ($path) {
    $brandingDisk = (string) env('TENANT_BRANDING_DISK', 'public');
    
    // Try the branding disk first
    if (Storage::disk($brandingDisk)->exists($path)) {
        return Storage::disk($brandingDisk)->response($path);
    }
    
    // Fallback to public disk
    if (Storage::disk('public')->exists($path)) {
        return Storage::disk('public')->response($path);
    }
    
    abort(404);
})->where('path', '.*');

Route::view('/view/submission/{token}', 'spa');
Route::view('/app/{any?}', 'spa')->where('any', '.*');
Route::view('/{any?}', 'spa')->where('any', '^(?!api).*$');
