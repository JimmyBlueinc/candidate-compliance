<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationDomain;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PlatformOrganizationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orgs = Organization::query()
            ->with(['domains' => function ($q) {
                $q->orderBy('domain');
            }])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (Organization $org) {
                return [
                    'id' => $org->id,
                    'name' => $org->name,
                    'slug' => $org->slug,
                    'is_active' => (bool) $org->is_active,
                    'domains' => $org->domains->map(fn (OrganizationDomain $d) => [
                        'id' => $d->id,
                        'domain' => $d->domain,
                        'is_active' => (bool) $d->is_active,
                    ])->values(),
                    'created_at' => $org->created_at?->toIso8601String(),
                    'updated_at' => $org->updated_at?->toIso8601String(),
                ];
            });

        return response()->json([
            'organizations' => $orgs,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:100', 'alpha_dash', 'unique:organizations,slug'],
            'domain' => ['nullable', 'string', 'max:255', 'unique:organization_domains,domain'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $org = Organization::create([
            'name' => trim($request->name),
            'slug' => Str::slug($request->slug),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $domain = null;
        if ($request->filled('domain')) {
            $domain = OrganizationDomain::create([
                'organization_id' => $org->id,
                'domain' => strtolower(trim($request->domain)),
                'is_active' => true,
            ]);
        }

        return response()->json([
            'message' => 'Organization created successfully',
            'organization' => [
                'id' => $org->id,
                'name' => $org->name,
                'slug' => $org->slug,
                'is_active' => (bool) $org->is_active,
                'domain' => $domain ? [
                    'id' => $domain->id,
                    'domain' => $domain->domain,
                    'is_active' => (bool) $domain->is_active,
                ] : null,
            ],
        ], 201);
    }

    public function addDomain(Request $request, Organization $organization): JsonResponse
    {
        $request->validate([
            'domain' => ['required', 'string', 'max:255', 'unique:organization_domains,domain'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $domain = OrganizationDomain::create([
            'organization_id' => $organization->id,
            'domain' => strtolower(trim($request->domain)),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'message' => 'Domain added successfully',
            'domain' => [
                'id' => $domain->id,
                'domain' => $domain->domain,
                'is_active' => (bool) $domain->is_active,
            ],
        ], 201);
    }

    public function createOwner(Request $request, Organization $organization): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $password = $request->filled('password') ? $request->password : Str::password(12);

        $owner = User::create([
            'organization_id' => $organization->id,
            'name' => htmlspecialchars(strip_tags($request->name), ENT_QUOTES, 'UTF-8'),
            'email' => filter_var($request->email, FILTER_SANITIZE_EMAIL),
            'password' => Hash::make($password),
            'role' => 'org_super_admin',
        ]);

        $tenantBaseDomain = config('app.tenant_base_domain') ?: env('TENANT_BASE_DOMAIN');
        $tenantUrl = $tenantBaseDomain
            ? ('https://' . $organization->slug . '.' . ltrim($tenantBaseDomain, '.'))
            : null;

        return response()->json([
            'message' => 'Organization owner created successfully',
            'tenant_url' => $tenantUrl,
            'owner' => [
                'id' => $owner->id,
                'name' => $owner->name,
                'email' => $owner->email,
                'role' => $owner->role,
                'organization_id' => $owner->organization_id,
            ],
            'temp_password' => $request->filled('password') ? null : $password,
        ], 201);
    }
}
