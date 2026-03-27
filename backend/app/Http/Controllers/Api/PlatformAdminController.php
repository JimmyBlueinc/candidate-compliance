<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Placement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlatformAdminController extends Controller
{
    public function platformHealth(Request $request): JsonResponse
    {
        $tenants = Organization::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $counts = Placement::query()
            ->withoutGlobalScopes()
            ->selectRaw('tenant_id, count(*) as active_count')
            ->where('stage', 'active')
            ->groupBy('tenant_id')
            ->pluck('active_count', 'tenant_id');

        $rows = $tenants->map(function ($t) use ($counts) {
            return [
                'tenant_id' => $t->id,
                'name' => $t->name,
                'slug' => $t->slug,
                'active_placements' => (int) ($counts[$t->id] ?? 0),
            ];
        });

        return response()->json([
            'data' => $rows,
        ]);
    }
}
