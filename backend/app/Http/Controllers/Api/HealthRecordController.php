<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HealthRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Support\Org;

class HealthRecordController extends Controller
{
    /**
     * Display a listing of health records.
     */
    public function index(Request $request): JsonResponse
    {
        $actor = $request->user();
        $orgId = Org::id($request);
        $effectiveOrgId = $actor?->role === 'platform_admin' ? $orgId : $actor?->organization_id;
        $query = HealthRecord::query();

        if ($effectiveOrgId) {
            $query->where('organization_id', $effectiveOrgId);
        }

        // Filter by candidate name
        if ($request->has('candidate_name')) {
            $query->where('candidate_name', 'like', '%' . $request->candidate_name . '%');
        }

        // Filter by record type
        if ($request->has('record_type')) {
            $query->where('record_type', $request->record_type);
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

        $healthRecords = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'data' => $healthRecords,
            'message' => 'Health records retrieved successfully',
        ]);
    }

    /**
     * Store a newly created health record.
     */
    public function store(Request $request): JsonResponse
    {
        $actor = $request->user();
        $orgId = Org::id($request);
        $effectiveOrgId = $actor?->role === 'platform_admin' ? $orgId : $actor?->organization_id;
        $validator = Validator::make($request->all(), [
            'candidate_name' => 'required|string|max:255',
            'record_type' => 'required|string|in:immunization,tb_test,health_screening,medical_clearance,fit_for_duty',
            'vaccine_type' => 'nullable|string|max:255',
            'dose_number' => 'nullable|integer|min:1',
            'administration_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:administration_date',
            'provider_name' => 'nullable|string|max:255',
            'status' => 'sometimes|string|in:up_to_date,expired,pending,due',
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
        $data['status'] = $data['status'] ?? 'pending';

        // Handle document upload
        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $path = $file->store('health_records', config('filesystems.default'));
            $data['document_path'] = $path;
        }

        $healthRecord = HealthRecord::create($data);

        return response()->json([
            'data' => $healthRecord,
            'message' => 'Health record created successfully',
        ], 201);
    }

    /**
     * Display the specified health record.
     */
    public function show(Request $request, HealthRecord $healthRecord): JsonResponse
    {
        $actor = $request->user();
        $orgId = Org::id($request);
        $effectiveOrgId = $actor?->role === 'platform_admin' ? $orgId : $actor?->organization_id;

        if ($effectiveOrgId && (int) $healthRecord->organization_id !== (int) $effectiveOrgId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if user has access
        if ($actor->role === 'candidate' && $healthRecord->user_id !== $actor->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'data' => $healthRecord,
            'message' => 'Health record retrieved successfully',
        ]);
    }

    /**
     * Update the specified health record.
     */
    public function update(Request $request, HealthRecord $healthRecord): JsonResponse
    {
        $actor = $request->user();
        $orgId = Org::id($request);
        $effectiveOrgId = $actor?->role === 'platform_admin' ? $orgId : $actor?->organization_id;

        if ($effectiveOrgId && (int) $healthRecord->organization_id !== (int) $effectiveOrgId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if user has access
        if ($actor->role === 'candidate' && $healthRecord->user_id !== $actor->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'candidate_name' => 'sometimes|string|max:255',
            'record_type' => 'sometimes|string|in:immunization,tb_test,health_screening,medical_clearance,fit_for_duty',
            'vaccine_type' => 'nullable|string|max:255',
            'dose_number' => 'nullable|integer|min:1',
            'administration_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:administration_date',
            'provider_name' => 'nullable|string|max:255',
            'status' => 'sometimes|string|in:up_to_date,expired,pending,due',
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
            if ($healthRecord->document_path) {
                Storage::disk(config('filesystems.default'))->delete($healthRecord->document_path);
            }
            $file = $request->file('document');
            $path = $file->store('health_records', config('filesystems.default'));
            $data['document_path'] = $path;
        }

        $healthRecord->update($data);

        return response()->json([
            'data' => $healthRecord->fresh(),
            'message' => 'Health record updated successfully',
        ]);
    }

    /**
     * Remove the specified health record.
     */
    public function destroy(Request $request, HealthRecord $healthRecord): JsonResponse
    {
        $actor = $request->user();
        $orgId = Org::id($request);
        $effectiveOrgId = $actor?->role === 'platform_admin' ? $orgId : $actor?->organization_id;

        if ($effectiveOrgId && (int) $healthRecord->organization_id !== (int) $effectiveOrgId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Only admins can delete
        if ($actor->role === 'candidate') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Delete document if exists
        if ($healthRecord->document_path) {
            Storage::disk(config('filesystems.default'))->delete($healthRecord->document_path);
        }

        $healthRecord->delete();

        return response()->json([
            'message' => 'Health record deleted successfully',
        ]);
    }
}

