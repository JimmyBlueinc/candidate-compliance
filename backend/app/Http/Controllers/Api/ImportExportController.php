<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Credential;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Support\Org;

class ImportExportController extends Controller
{
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();
        $data = array_map('str_getcsv', file($path));
        
        // Remove header row
        $header = array_shift($data);
        
        $imported = 0;
        $errors = [];
        $user = $request->user();
        $orgId = Org::id($request);

        foreach ($data as $index => $row) {
            try {
                // Map CSV columns to credential fields
                $credentialData = [
                    'organization_id' => $orgId,
                    'candidate_name' => $row[0] ?? '',
                    'position' => $row[1] ?? '',
                    'credential_type' => $row[2] ?? '',
                    'issue_date' => !empty($row[3]) ? date('Y-m-d', strtotime($row[3])) : null,
                    'expiry_date' => !empty($row[4]) ? date('Y-m-d', strtotime($row[4])) : null,
                    'email' => $row[5] ?? ($user->role === 'candidate' ? $user->email : ''),
                    'status' => $row[6] ?? 'active',
                ];

                // Validate
                $validator = Validator::make($credentialData, [
                    'candidate_name' => 'required|string|max:255',
                    'credential_type' => 'required|string|max:255',
                    'email' => 'required|email',
                ]);

                if ($validator->fails()) {
                    $errors[] = "Row " . ($index + 2) . ": " . implode(', ', $validator->errors()->all());
                    continue;
                }

                // Check if candidate can only create for their own email
                if ($user->role === 'candidate' && $credentialData['email'] !== $user->email) {
                    $errors[] = "Row " . ($index + 2) . ": Candidates can only create credentials with their own email";
                    continue;
                }

                Credential::create($credentialData);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
            }
        }

        return response()->json([
            'message' => 'Import completed',
            'imported' => $imported,
            'errors' => $errors,
            'total_rows' => count($data),
        ]);
    }
}
