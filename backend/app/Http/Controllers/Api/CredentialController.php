<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCredentialRequest;
use App\Http\Requests\UpdateCredentialRequest;
use App\Models\Credential;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Support\Org;

class CredentialController extends Controller
{
    /**
     * Display a listing of the resource with filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        $query = Credential::with('user:id,name,email');
        if ($orgId) {
            $query->where('organization_id', $orgId);
        }
        
        // Role-based filtering
        $user = $request->user();
        if ($user->role === 'candidate') {
            // Candidates can only see credentials with their email
            $query->where('email', $user->email);
        }
        // Admins and org_super_admins can see all credentials in org

        // Filter by candidate name (sanitized to prevent SQL injection)
        if ($request->has('name')) {
            $name = $request->input('name');
            // Eloquent automatically escapes LIKE queries, but we'll sanitize for extra safety
            $name = str_replace(['%', '_'], ['\%', '\_'], $name); // Escape LIKE wildcards
            $query->where('candidate_name', 'like', '%' . $name . '%');
        }

        // Filter by credential type (sanitized to prevent SQL injection)
        if ($request->has('type')) {
            $type = $request->input('type');
            // Eloquent automatically escapes LIKE queries, but we'll sanitize for extra safety
            $type = str_replace(['%', '_'], ['\%', '\_'], $type); // Escape LIKE wildcards
            $query->where('credential_type', 'like', '%' . $type . '%');
        }

        // Filter by email (for candidates)
        if ($request->has('email')) {
            $email = $request->input('email');
            $query->where('email', $email);
        }

        // Filter by user_id (admin/org_super_admin only)
        if ($request->filled('user_id')) {
            $actor = $request->user();
            if (!in_array($actor?->role, ['admin', 'org_super_admin'], true)) {
                return response()->json([
                    'message' => 'Unauthorized.',
                ], 403);
            }

            $targetUser = User::query()->findOrFail((int) $request->input('user_id'));
            if ($orgId && (int) $targetUser->organization_id !== (int) $orgId) {
                return response()->json([
                    'message' => 'Target user does not belong to this organization.',
                ], 403);
            }

            $query->where('user_id', (int) $targetUser->id);
        }

        // Pagination
        $perPage = (int) $request->integer('per_page', 10);
        if ($perPage < 1) {
            $perPage = 10;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }
        $paginator = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Add calculated status to each credential
        $paginator->getCollection()->transform(function ($credential) {
            $calculatedStatus = $credential->getCalculatedStatus();
            // Use manual status if set (not null and not empty), otherwise use calculated status
            $status = ($credential->status && $credential->status !== '') 
                ? $credential->status 
                : $calculatedStatus['status'];
            $statusColor = ($credential->status && $credential->status !== '') 
                ? $this->getStatusColor($credential->status) 
                : $calculatedStatus['color'];
            
            return [
                'id' => $credential->id,
                'user_id' => $credential->user_id,
                'user' => $credential->user ? [
                    'id' => $credential->user->id,
                    'name' => $credential->user->name,
                    'email' => $credential->user->email,
                ] : null,
                'candidate_name' => $credential->candidate_name,
                'position' => $credential->position,
                'specialty' => $credential->specialty,
                'province' => $credential->province,
                'credential_type' => $credential->credential_type,
                'issue_date' => $credential->issue_date?->format('Y-m-d'),
                'expiry_date' => $credential->expiry_date?->format('Y-m-d'),
                'email' => $credential->email,
                'status' => $status,
                'status_color' => $statusColor,
                'calculated_status' => $calculatedStatus['status'],
                'calculated_status_color' => $calculatedStatus['color'],
                'document_url' => $credential->document_url,
                'created_at' => $credential->created_at?->toISOString(),
                'updated_at' => $credential->updated_at?->toISOString(),
            ];
        });

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCredentialRequest $request): JsonResponse
    {
        $user = $request->user();
        $orgId = Org::id($request);
        
        // Candidates can only create credentials with their own email
        if ($user->role === 'candidate') {
            if ($request->input('email') !== $user->email) {
                return response()->json([
                    'message' => 'Candidates can only create credentials with their own email address.'
                ], 403);
            }
        }
        
        // Default: create credential for authenticated user
        $userId = $user->id;

        // Admin/org_super_admin can create credentials for a selected org user
        if (in_array($user->role, ['admin', 'org_super_admin'], true) && $request->filled('user_id')) {
            $targetUser = User::query()->findOrFail((int) $request->input('user_id'));

            if ($orgId && (int) $targetUser->organization_id !== (int) $orgId) {
                return response()->json([
                    'message' => 'Target user does not belong to this organization.',
                ], 403);
            }

            if ($targetUser->role === 'platform_admin') {
                return response()->json([
                    'message' => 'Cannot create credentials for platform_admin users.',
                ], 403);
            }

            $userId = $targetUser->id;
        }

        $credential = Credential::create([
            'organization_id' => $orgId,
            'user_id' => $userId,
            'candidate_name' => $request->input('candidate_name'),
            'position' => $request->input('position'),
            'credential_type' => $request->input('credential_type'),
            'issue_date' => $request->input('issue_date'),
            'expiry_date' => $request->input('expiry_date'),
            'email' => $request->input('email'),
            'status' => $request->input('status') ?: null, // Allow manual status, null if empty to use calculated
        ]);

        // Handle document upload
        if ($request->hasFile('document')) {
            $path = $request->file('document')->store('credentials', config('filesystems.default'));
            $credential->document_path = $path;
            $credential->save();
        }

        // Log activity
        ActivityLog::create([
            'organization_id' => $orgId,
            'user_id' => $user->id,
            'action' => 'created',
            'entity' => 'credential',
            'entity_name' => $credential->candidate_name . ' - ' . $credential->credential_type,
            'entity_id' => $credential->id,
            'description' => "Created new credential: {$credential->candidate_name}",
        ]);

        $calculatedStatus = $credential->getCalculatedStatus();
        // Use manual status if set (not null and not empty), otherwise use calculated status
        $status = ($credential->status && $credential->status !== '') 
            ? $credential->status 
            : $calculatedStatus['status'];
        $statusColor = ($credential->status && $credential->status !== '') 
            ? $this->getStatusColor($credential->status) 
            : $calculatedStatus['color'];

        return response()->json([
            'data' => [
                'id' => $credential->id,
                'user_id' => $credential->user_id,
                'candidate_name' => $credential->candidate_name,
                'position' => $credential->position,
                'specialty' => $credential->specialty,
                'province' => $credential->province,
                'credential_type' => $credential->credential_type,
                'issue_date' => $credential->issue_date?->format('Y-m-d'),
                'expiry_date' => $credential->expiry_date?->format('Y-m-d'),
                'email' => $credential->email,
                'status' => $status,
                'status_color' => $statusColor,
                'calculated_status' => $calculatedStatus['status'],
                'calculated_status_color' => $calculatedStatus['color'],
                'document_url' => $credential->document_url,
                'created_at' => $credential->created_at?->toISOString(),
                'updated_at' => $credential->updated_at?->toISOString(),
            ],
            'message' => 'Credential created successfully',
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $orgId = Org::id($request);
        $credential = Credential::with('user:id,name,email')
            ->when($orgId, fn ($q) => $q->where('organization_id', $orgId))
            ->findOrFail($id);
        
        $user = $request->user();
        
        // Candidates can only view credentials with their email
        if ($user->role === 'candidate' && $credential->email !== $user->email) {
            return response()->json([
                'message' => 'Unauthorized. You can only view your own credentials.',
            ], 403);
        }
        
        $calculatedStatus = $credential->getCalculatedStatus();
        // Use manual status if set (not null and not empty), otherwise use calculated status
        $status = ($credential->status && $credential->status !== '') 
            ? $credential->status 
            : $calculatedStatus['status'];
        $statusColor = ($credential->status && $credential->status !== '') 
            ? $this->getStatusColor($credential->status) 
            : $calculatedStatus['color'];

        return response()->json([
            'data' => [
                'id' => $credential->id,
                'user_id' => $credential->user_id,
                'user' => $credential->user ? [
                    'id' => $credential->user->id,
                    'name' => $credential->user->name,
                    'email' => $credential->user->email,
                ] : null,
                'candidate_name' => $credential->candidate_name,
                'position' => $credential->position,
                'specialty' => $credential->specialty,
                'province' => $credential->province,
                'credential_type' => $credential->credential_type,
                'issue_date' => $credential->issue_date?->format('Y-m-d'),
                'expiry_date' => $credential->expiry_date?->format('Y-m-d'),
                'email' => $credential->email,
                'status' => $status,
                'status_color' => $statusColor,
                'calculated_status' => $calculatedStatus['status'],
                'calculated_status_color' => $calculatedStatus['color'],
                'document_url' => $credential->document_url,
                'created_at' => $credential->created_at?->toISOString(),
                'updated_at' => $credential->updated_at?->toISOString(),
            ],
        ]);
    }

    /**
     * Get color for manual status.
     */
    private function getStatusColor(string $status): string
    {
        return match ($status) {
            'active' => 'green',
            'expiring_soon' => 'yellow',
            'expired' => 'red',
            'pending' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCredentialRequest $request, string $id): JsonResponse
    {
        $orgId = Org::id($request);
        $credential = Credential::when($orgId, fn ($q) => $q->where('organization_id', $orgId))
            ->findOrFail($id);
        $user = $request->user();
        
        // Candidates can only update credentials with their email
        if ($user->role === 'candidate') {
            if ($credential->email !== $user->email) {
                return response()->json([
                    'message' => 'Unauthorized. You can only update your own credentials.',
                ], 403);
            }
            // Candidates cannot change the email on their credentials
            if ($request->has('email') && $request->input('email') !== $user->email) {
                return response()->json([
                    'message' => 'Candidates cannot change the email address on credentials.',
                ], 403);
            }
        }

        $validated = $request->validated();
        // If status is empty string, set it to null to allow auto-calculation
        if (isset($validated['status']) && $validated['status'] === '') {
            $validated['status'] = null;
        }

        // Handle document upload replace (optional)
        if ($request->hasFile('document')) {
            $path = $request->file('document')->store('credentials', config('filesystems.default'));
            $validated['document_path'] = $path;
        }

        $oldName = $credential->candidate_name;
        $credential->update($validated);

        $credential->refresh();
        
        // Log activity
        ActivityLog::create([
            'organization_id' => $orgId,
            'user_id' => $user->id,
            'action' => 'updated',
            'entity' => 'credential',
            'entity_name' => $credential->candidate_name . ' - ' . $credential->credential_type,
            'entity_id' => $credential->id,
            'description' => "Updated credential: {$oldName}",
        ]);
        $calculatedStatus = $credential->getCalculatedStatus();
        // Use manual status if set (not null and not empty), otherwise use calculated status
        $status = ($credential->status && $credential->status !== '') 
            ? $credential->status 
            : $calculatedStatus['status'];
        $statusColor = ($credential->status && $credential->status !== '') 
            ? $this->getStatusColor($credential->status) 
            : $calculatedStatus['color'];

        return response()->json([
            'data' => [
                'id' => $credential->id,
                'user_id' => $credential->user_id,
                'candidate_name' => $credential->candidate_name,
                'position' => $credential->position,
                'specialty' => $credential->specialty,
                'province' => $credential->province,
                'credential_type' => $credential->credential_type,
                'issue_date' => $credential->issue_date?->format('Y-m-d'),
                'expiry_date' => $credential->expiry_date?->format('Y-m-d'),
                'email' => $credential->email,
                'status' => $status,
                'status_color' => $statusColor,
                'calculated_status' => $calculatedStatus['status'],
                'calculated_status_color' => $calculatedStatus['color'],
                'document_url' => $credential->document_url,
                'created_at' => $credential->created_at?->toISOString(),
                'updated_at' => $credential->updated_at?->toISOString(),
            ],
            'message' => 'Credential updated successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $orgId = Org::id($request);
        $credential = Credential::when($orgId, fn ($q) => $q->where('organization_id', $orgId))
            ->findOrFail($id);
        $user = $request->user();
        
        // Candidates can only delete credentials with their email
        if ($user->role === 'candidate' && $credential->email !== $user->email) {
            return response()->json([
                'message' => 'Unauthorized. You can only delete your own credentials.',
            ], 403);
        }
        
        $credentialName = $credential->candidate_name . ' - ' . $credential->credential_type;
        $credentialId = $credential->id;
        
        $credential->delete();

        // Log activity
        ActivityLog::create([
            'organization_id' => $orgId,
            'user_id' => $user->id,
            'action' => 'deleted',
            'entity' => 'credential',
            'entity_name' => $credentialName,
            'entity_id' => $credentialId,
            'description' => "Deleted credential: {$credentialName}",
        ]);

        return response()->json([
            'message' => 'Credential deleted successfully',
        ]);
    }
}
