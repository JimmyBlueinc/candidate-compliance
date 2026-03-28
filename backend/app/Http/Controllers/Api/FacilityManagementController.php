<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\AdminUserWelcomeMail;
use App\Models\Facility;
use App\Models\User;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FacilityManagementController extends Controller
{
    public function export(Request $request): StreamedResponse|JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $facilities = Facility::query()
            ->where('organization_id', $orgId)
            ->withCount('users')
            ->withCount('contracts')
            ->orderBy('name')
            ->get();

        $filename = 'facilities_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($facilities) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'id',
                'name',
                'facility_type',
                'city',
                'state',
                'country',
                'users_count',
                'contracts_count',
                'contact_person_name',
                'contact_email',
                'created_at',
            ]);

            foreach ($facilities as $f) {
                fputcsv($handle, [
                    $f->id,
                    $f->name,
                    $f->facility_type,
                    $f->city,
                    $f->state,
                    $f->country,
                    (int) ($f->users_count ?? 0),
                    (int) ($f->contracts_count ?? 0),
                    $f->contact_person_name,
                    $f->contact_email,
                    $f->created_at?->toIso8601String(),
                ]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function index(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $facilities = Facility::query()
            ->where('organization_id', $orgId)
            ->withCount('users')
            ->withCount('contracts')
            ->orderBy('name')
            ->get()
            ->map(function (Facility $f) {
                return [
                    'id' => $f->id,
                    'name' => $f->name,
                    'address' => $f->address,
                    'city' => $f->city,
                    'state' => $f->state,
                    'country' => $f->country,
                    'postal_code' => $f->postal_code,
                    'timezone' => $f->timezone,
                    'facility_type' => $f->facility_type,
                    'facility_type_other' => $f->facility_type_other,
                    'contact_person_name' => $f->contact_person_name,
                    'contact_email' => $f->contact_email,
                    'contact_phone' => $f->contact_phone,
                    'users_count' => (int) ($f->users_count ?? 0),
                    'contracts_count' => (int) ($f->contracts_count ?? 0),
                    'created_at' => $f->created_at?->toIso8601String(),
                    'updated_at' => $f->updated_at?->toIso8601String(),
                ];
            });

        return response()->api($facilities);
    }

    public function store(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:40'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'facility_type' => ['nullable', 'string', 'max:120'],
            'facility_type_other' => ['nullable', 'string', 'max:120'],
            'contact_person_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'string', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
        ]);

        $facility = Facility::create([
            'organization_id' => $orgId,
            'name' => htmlspecialchars(strip_tags($validated['name']), ENT_QUOTES, 'UTF-8'),
            'address' => isset($validated['address']) ? htmlspecialchars(strip_tags((string) $validated['address']), ENT_QUOTES, 'UTF-8') : null,
            'city' => isset($validated['city']) ? htmlspecialchars(strip_tags((string) $validated['city']), ENT_QUOTES, 'UTF-8') : null,
            'state' => isset($validated['state']) ? htmlspecialchars(strip_tags((string) $validated['state']), ENT_QUOTES, 'UTF-8') : null,
            'country' => isset($validated['country']) ? htmlspecialchars(strip_tags((string) $validated['country']), ENT_QUOTES, 'UTF-8') : null,
            'postal_code' => isset($validated['postal_code']) ? htmlspecialchars(strip_tags((string) $validated['postal_code']), ENT_QUOTES, 'UTF-8') : null,
            'timezone' => isset($validated['timezone']) ? htmlspecialchars(strip_tags((string) $validated['timezone']), ENT_QUOTES, 'UTF-8') : null,
            'facility_type' => isset($validated['facility_type']) ? htmlspecialchars(strip_tags((string) $validated['facility_type']), ENT_QUOTES, 'UTF-8') : null,
            'facility_type_other' => isset($validated['facility_type_other']) ? htmlspecialchars(strip_tags((string) $validated['facility_type_other']), ENT_QUOTES, 'UTF-8') : null,
            'contact_person_name' => isset($validated['contact_person_name']) ? htmlspecialchars(strip_tags((string) $validated['contact_person_name']), ENT_QUOTES, 'UTF-8') : null,
            'contact_email' => $validated['contact_email'] ?? null,
            'contact_phone' => isset($validated['contact_phone']) ? htmlspecialchars(strip_tags((string) $validated['contact_phone']), ENT_QUOTES, 'UTF-8') : null,
        ]);

        return response()->api([
            'facility' => [
                'id' => $facility->id,
                'name' => $facility->name,
                'address' => $facility->address,
                'city' => $facility->city,
                'state' => $facility->state,
                'country' => $facility->country,
                'postal_code' => $facility->postal_code,
                'timezone' => $facility->timezone,
                'facility_type' => $facility->facility_type,
                'facility_type_other' => $facility->facility_type_other,
                'contact_person_name' => $facility->contact_person_name,
                'contact_email' => $facility->contact_email,
                'contact_phone' => $facility->contact_phone,
            ],
        ], 201, [], 'Facility created.');
    }

    public function createFacilityUser(Request $request, Facility $facility): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        if ((int) $facility->organization_id !== (int) $orgId) {
            return response()->json(['message' => 'Facility not found.'], 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->where(fn ($q) => $q->where('organization_id', $orgId)),
            ],
            'role' => ['sometimes', 'string', Rule::in(['facility'])],
        ]);

        $tempPassword = Str::password(12);

        $user = User::create([
            'organization_id' => $orgId,
            'facility_id' => $facility->id,
            'name' => htmlspecialchars(strip_tags($validated['name']), ENT_QUOTES, 'UTF-8'),
            'email' => filter_var($validated['email'], FILTER_SANITIZE_EMAIL),
            'password' => Hash::make($tempPassword),
            'role' => 'facility',
            'must_change_password' => true,
        ]);

        $emailSent = false;
        try {
            $organizationName = (string) ($user->organization?->name ?? '');
            $loginUrl = url('/login');

            Mail::to($user->email)->send(new AdminUserWelcomeMail(
                $organizationName,
                $loginUrl,
                $user->email,
                $tempPassword,
            ));

            $emailSent = true;
        } catch (\Throwable $e) {
            Log::error('Failed sending facility credentials email', [
                'created_user_id' => $user->id,
                'created_user_email' => $user->email,
                'organization_id' => $user->organization_id,
                'facility_id' => $user->facility_id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->api([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'facility_id' => $user->facility_id,
                'avatar_url' => $user->avatar_url,
            ],
            'credentials' => [
                'email' => $user->email,
                'temp_password' => $tempPassword,
            ],
            'email_sent' => $emailSent,
        ], 201, [], 'Facility user created.');
    }
}
