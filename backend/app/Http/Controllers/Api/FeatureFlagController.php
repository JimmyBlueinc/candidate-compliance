<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeatureFlag;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FeatureFlagController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        $flags = FeatureFlag::query()
            ->where(function ($q) use ($orgId) {
                $q->whereNull('organization_id');
                if ($orgId) {
                    $q->orWhere('organization_id', $orgId);
                }
            })
            ->orderBy('key')
            ->get()
            ->mapWithKeys(fn ($f) => [
                $f->key => [
                    'enabled' => (bool) $f->enabled,
                    'payload' => $f->payload,
                ],
            ]);

        return response()->api(['flags' => $flags]);
    }

    public function upsert(Request $request, string $key): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'enabled' => ['required', 'boolean'],
            'payload' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $orgId = Org::id($request);
        $flag = FeatureFlag::updateOrCreate(
            ['organization_id' => $orgId, 'key' => $key],
            ['enabled' => (bool) $request->boolean('enabled'), 'payload' => $request->input('payload')]
        );

        return response()->api(['flag' => $flag], 200, [], 'Feature flag updated.');
    }
}
