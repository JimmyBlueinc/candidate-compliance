<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BackgroundCheck;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Support\Org;

class BackgroundCheckController extends Controller
{
    /**
     * Display a listing of background checks.
     */
    public function index(Request $request): JsonResponse
    {
        $actor = $request->user();
        $orgId = Org::id($request);
        $effectiveOrgId = $actor?->role === 'platform_admin' ? $orgId : $actor?->organization_id;
        $query = BackgroundCheck::query();

        if ($effectiveOrgId) {
            $query->where('organization_id', $effectiveOrgId);
        }

        // Filter by candidate name
        if ($request->has('candidate_name')) {
            $query->where('candidate_name', 'like', '%' . $request->candidate_name . '%');
        }

        // Filter by check type
        if ($request->has('check_type')) {
            $query->where('check_type', $request->check_type);
        }

        // Filter by verification status
        if ($request->has('verification_status')) {
            $query->where('verification_status', $request->verification_status);
        }

        // Candidates can only view their own
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

        $backgroundChecks = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'data' => $backgroundChecks,
            'message' => 'Background checks retrieved successfully',
        ]);
    }

    /**
     * Store a newly created background check.
     */
    public function store(Request $request): JsonResponse
    {
        $actor = $request->user();
        $orgId = Org::id($request);
        $effectiveOrgId = $actor?->role === 'platform_admin' ? $orgId : $actor?->organization_id;
        $validator = Validator::make($request->all(), [
            'candidate_name' => 'required|string|max:255',
            'check_type' => 'required|string|in:criminal_record_check,vulnerable_sector_check,security_clearance,other',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:issue_date',
            'verification_status' => 'sometimes|string|in:pending,verified,failed,expired',
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
        $data['verification_status'] = $data['verification_status'] ?? 'pending';

        // Handle document upload
        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $path = $file->store('background_checks', config('filesystems.default'));
            $data['document_path'] = $path;
        }

        $backgroundCheck = BackgroundCheck::create($data);

        return response()->json([
            'data' => $backgroundCheck,
            'message' => 'Background check created successfully',
        ], 201);
    }

    /**
     * Display the specified background check.
     */
    public function show(Request $request, BackgroundCheck $backgroundCheck): JsonResponse
    {
        $orgId = Org::id($request);

        if ($orgId && (int) $backgroundCheck->organization_id !== (int) $orgId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if user has access
        if ($request->user()->role === 'candidate' && $backgroundCheck->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'data' => $backgroundCheck,
            'message' => 'Background check retrieved successfully',
        ]);
    }

    /**
     * Update the specified background check.
     */
    public function update(Request $request, BackgroundCheck $backgroundCheck): JsonResponse
    {
        $orgId = Org::id($request);

        if ($orgId && (int) $backgroundCheck->organization_id !== (int) $orgId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if user has access
        if ($request->user()->role === 'candidate' && $backgroundCheck->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'candidate_name' => 'sometimes|string|max:255',
            'check_type' => 'sometimes|string|in:criminal_record_check,vulnerable_sector_check,security_clearance,other',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:issue_date',
            'verification_status' => 'sometimes|string|in:pending,verified,failed,expired',
            'verified_by' => 'nullable|exists:users,id',
            'verification_date' => 'nullable|date',
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
            if ($backgroundCheck->document_path) {
                Storage::disk(config('filesystems.default'))->delete($backgroundCheck->document_path);
            }
            $file = $request->file('document');
            $path = $file->store('background_checks', config('filesystems.default'));
            $data['document_path'] = $path;
        }

        // If admin is verifying, set verified_by and verification_date
        if ($request->has('verification_status') && $request->verification_status === 'verified' && 
            ($request->user()->role === 'admin' || $request->user()->role === 'org_super_admin')) {
            if (!isset($data['verified_by'])) {
                $data['verified_by'] = $request->user()->id;
            }
            if (!isset($data['verification_date'])) {
                $data['verification_date'] = now()->toDateString();
            }
        }

        $backgroundCheck->update($data);

        return response()->json([
            'data' => $backgroundCheck->fresh(),
            'message' => 'Background check updated successfully',
        ]);
    }

    /**
     * Remove the specified background check.
     */
    public function destroy(Request $request, BackgroundCheck $backgroundCheck): JsonResponse
    {
        $orgId = Org::id($request);

        if ($orgId && (int) $backgroundCheck->organization_id !== (int) $orgId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Only admins can delete
        if ($request->user()->role === 'candidate') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Delete document if exists
        if ($backgroundCheck->document_path) {
            Storage::disk(config('filesystems.default'))->delete($backgroundCheck->document_path);
        }

        $backgroundCheck->delete();

        return response()->json([
            'message' => 'Background check deleted successfully',
        ]);
    }
}

