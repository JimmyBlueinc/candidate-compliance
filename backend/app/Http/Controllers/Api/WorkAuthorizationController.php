<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkAuthorization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Support\Org;

class WorkAuthorizationController extends Controller
{
    /**
     * Display a listing of work authorizations.
     */
    public function index(Request $request): JsonResponse
    {
        $actor = $request->user();
        $orgId = Org::id($request);
        $effectiveOrgId = $actor?->role === 'platform_admin' ? $orgId : $actor?->organization_id;
        $query = WorkAuthorization::query();

        if ($effectiveOrgId) {
            $query->where('organization_id', $effectiveOrgId);
        }

        // Filter by candidate name
        if ($request->has('candidate_name')) {
            $query->where('candidate_name', 'like', '%' . $request->candidate_name . '%');
        }

        // Filter by authorization type
        if ($request->has('authorization_type')) {
            $query->where('authorization_type', $request->authorization_type);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by user_id (for candidates viewing their own)
        if ($actor->role === 'candidate') {
            $query->where('user_id', $actor->id);
        }

        // Admin/org_super_admin can optionally scope by user_id (e.g. view per recruiter/admin)
        if ($request->filled('user_id')) {
            if (!in_array($actor?->role, ['admin', 'org_super_admin'], true)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $targetUser = User::query()->findOrFail((int) $request->input('user_id'));
            if ($effectiveOrgId && (int) $targetUser->organization_id !== (int) $effectiveOrgId) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $query->where('user_id', $targetUser->id);
        }

        $workAuthorizations = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'data' => $workAuthorizations,
            'message' => 'Work authorizations retrieved successfully',
        ]);
    }

    /**
     * Store a newly created work authorization.
     */
    public function store(Request $request): JsonResponse
    {
        $actor = $request->user();
        $orgId = Org::id($request);
        $effectiveOrgId = $actor?->role === 'platform_admin' ? $orgId : $actor?->organization_id;
        $validator = Validator::make($request->all(), [
            'candidate_name' => 'required|string|max:255',
            'authorization_type' => 'required|string|in:work_permit,visa,sin,professional_liability_insurance,other',
            'document_number' => 'nullable|string|max:255',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:issue_date',
            'status' => 'sometimes|string|in:valid,expired,pending_renewal,revoked',
            'notes' => 'nullable|string',
            'document' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240', // 10MB max
            'user_id' => 'sometimes|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Candidates can only create for themselves
        if ($actor->role === 'candidate') {
            $data['user_id'] = $actor->id;
        } else {
            $targetUserId = $data['user_id'] ?? $actor->id;
            if (isset($data['user_id'])) {
                $targetUser = User::query()->findOrFail((int) $data['user_id']);
                if ($effectiveOrgId && (int) $targetUser->organization_id !== (int) $effectiveOrgId) {
                    return response()->json(['message' => 'Unauthorized'], 403);
                }
            }
            $data['user_id'] = $targetUserId;
        }
        $data['organization_id'] = $effectiveOrgId;
        $data['status'] = $data['status'] ?? 'valid';

        // Handle document upload
        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $path = $file->store('work_authorizations', config('filesystems.default'));
            $data['document_path'] = $path;
        }

        $workAuthorization = WorkAuthorization::create($data);

        return response()->json([
            'data' => $workAuthorization,
            'message' => 'Work authorization created successfully',
        ], 201);
    }

    /**
     * Display the specified work authorization.
     */
    public function show(Request $request, WorkAuthorization $workAuthorization): JsonResponse
    {
        $orgId = Org::id($request);

        if ($orgId && (int) $workAuthorization->organization_id !== (int) $orgId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if user has access
        if ($request->user()->role === 'candidate' && $workAuthorization->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'data' => $workAuthorization,
            'message' => 'Work authorization retrieved successfully',
        ]);
    }

    /**
     * Update the specified work authorization.
     */
    public function update(Request $request, WorkAuthorization $workAuthorization): JsonResponse
    {
        $orgId = Org::id($request);

        if ($orgId && (int) $workAuthorization->organization_id !== (int) $orgId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if user has access
        if ($request->user()->role === 'candidate' && $workAuthorization->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'candidate_name' => 'sometimes|string|max:255',
            'authorization_type' => 'sometimes|string|in:work_permit,visa,sin,professional_liability_insurance,other',
            'document_number' => 'nullable|string|max:255',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:issue_date',
            'status' => 'sometimes|string|in:valid,expired,pending_renewal,revoked',
            'verified_date' => 'nullable|date',
            'verified_by' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
            'document' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Handle document upload
        if ($request->hasFile('document')) {
            // Delete old document if exists
            if ($workAuthorization->document_path) {
                Storage::disk(config('filesystems.default'))->delete($workAuthorization->document_path);
            }
            $file = $request->file('document');
            $path = $file->store('work_authorizations', config('filesystems.default'));
            $data['document_path'] = $path;
        }

        // If admin is verifying, set verified_by and verified_date
        if ($request->has('status') && $request->status === 'valid' && 
            ($request->user()->role === 'admin' || $request->user()->role === 'org_super_admin')) {
            if (!isset($data['verified_by'])) {
                $data['verified_by'] = $request->user()->id;
            }
            if (!isset($data['verified_date'])) {
                $data['verified_date'] = now()->toDateString();
            }
        }

        $workAuthorization->update($data);

        return response()->json([
            'data' => $workAuthorization->fresh(),
            'message' => 'Work authorization updated successfully',
        ]);
    }

    /**
     * Remove the specified work authorization.
     */
    public function destroy(Request $request, WorkAuthorization $workAuthorization): JsonResponse
    {
        $orgId = Org::id($request);

        if ($orgId && (int) $workAuthorization->organization_id !== (int) $orgId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Only admins can delete
        if ($request->user()->role === 'candidate') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Delete document if exists
        if ($workAuthorization->document_path) {
            Storage::disk(config('filesystems.default'))->delete($workAuthorization->document_path);
        }

        $workAuthorization->delete();

        return response()->json([
            'message' => 'Work authorization deleted successfully',
        ]);
    }
}

