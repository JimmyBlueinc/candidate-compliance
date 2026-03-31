<?php

namespace App\Http\Controllers;

use App\Models\JobOrder;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicJobController extends Controller
{
    public function index(Request $request, string $slug): JsonResponse
    {
        $org = Organization::query()->where('slug', $slug)->first();
        if (!$org) {
            return response()->json([
                'message' => 'Organization not found.',
            ], 404);
        }

        $query = JobOrder::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $org->id)
            ->where('published', true)
            ->where('status', 'open');

        if ($request->filled('specialty')) {
            $query->where('specialty', $request->input('specialty'));
        }

        if ($request->filled('q')) {
            $q = trim((string) $request->input('q'));
            if ($q !== '') {
                $query->where(function ($sub) use ($q) {
                    $sub->where('title', 'like', '%' . $q . '%')
                        ->orWhere('facility_name', 'like', '%' . $q . '%')
                        ->orWhere('specialty', 'like', '%' . $q . '%');
                });
            }
        }

        $rows = $query
            ->orderByDesc('created_at')
            ->limit(500)
            ->get([
                'id',
                'title',
                'facility_name',
                'specialty',
                'bill_rate',
                'pay_rate',
                'stipend_weekly',
                'work_mode',
                'start_date',
                'source',
                'external_id',
                'created_at',
            ]);

        return response()->json([
            'organization' => [
                'id' => $org->id,
                'name' => $org->name,
                'slug' => $org->slug,
                'primary_color' => $org->primary_color,
            ],
            'data' => $rows,
        ]);
    }
}
