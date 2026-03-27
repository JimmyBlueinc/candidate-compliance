<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\CandidatePipeline;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CandidateImportController extends Controller
{
    private const SUPPORTED_FIELDS = [
        'first_name',
        'last_name',
        'full_name',
        'email',
        'phone',
        'specialty',
        'license_type',
        'years_experience',
        'city',
        'state',
        'source',
        'notes',
    ];

    public function template(): \Symfony\Component\HttpFoundation\Response
    {
        $headers = [
            'First Name',
            'Last Name',
            'Full Name',
            'Email',
            'Phone',
            'Specialty',
            'License Type',
            'Years Experience',
            'City',
            'State',
            'Source',
            'Notes',
        ];

        $content = implode(',', array_map([$this, 'csvEscape'], $headers)) . "\n";

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="agencyhq-candidates-template.csv"',
        ]);
    }

    public function upload(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $request->validate([
            'file' => ['required', 'file', 'max:20480', 'mimes:csv,txt,xlsx'],
        ]);

        $file = $request->file('file');
        $ext = strtolower((string) $file->getClientOriginalExtension());
        if (!in_array($ext, ['csv', 'txt', 'xlsx'], true)) {
            return response()->json(['message' => 'Unsupported file type.'], 422);
        }

        $uploadId = (string) Str::uuid();
        $disk = 'local';
        $path = "private/tenants/{$orgId}/imports/candidates/{$uploadId}.{$ext}";
        Storage::disk($disk)->putFileAs("private/tenants/{$orgId}/imports/candidates", $file, "{$uploadId}.{$ext}");

        [$headers, $rows] = $this->readFile(Storage::disk($disk)->path($path));

        $headers = $this->normalizeHeaders($headers);
        $sample = array_slice($rows, 0, 25);

        return response()->api([
            'upload_id' => $uploadId,
            'headers' => $headers,
            'sample_rows' => $sample,
            'total_rows' => count($rows),
        ]);
    }

    public function preview(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $validated = $request->validate([
            'upload_id' => ['required', 'string'],
            'mapping' => ['required', 'array'],
        ]);

        $uploadId = (string) $validated['upload_id'];
        $mapping = (array) $validated['mapping'];

        $filePath = $this->resolveUploadPath($orgId, $uploadId);
        if (!$filePath) {
            return response()->json(['message' => 'Upload not found.'], 404);
        }

        [$headers, $rows] = $this->readFile($filePath);
        $headers = $this->normalizeHeaders($headers);

        $previewRows = [];
        $max = min(count($rows), 200);
        $existing = $this->buildExistingIndex($orgId);

        for ($i = 0; $i < $max; $i++) {
            $raw = $rows[$i];
            $candidateData = $this->mapRow($raw, $headers, $mapping);
            $result = $this->validateAndClassifyRow($candidateData, $existing);

            $previewRows[] = [
                'row_number' => $i + 2,
                'data' => $candidateData,
                'status' => $result['status'],
                'reasons' => $result['reasons'],
                'duplicate_key' => $result['duplicate_key'],
            ];
        }

        $summary = $this->summarize($previewRows);

        return response()->api([
            'headers' => $headers,
            'total_rows' => count($rows),
            'preview_rows' => $previewRows,
            'summary' => $summary,
        ]);
    }

    public function commit(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $validated = $request->validate([
            'upload_id' => ['required', 'string'],
            'mapping' => ['required', 'array'],
        ]);

        $uploadId = (string) $validated['upload_id'];
        $mapping = (array) $validated['mapping'];

        $filePath = $this->resolveUploadPath($orgId, $uploadId);
        if (!$filePath) {
            return response()->json(['message' => 'Upload not found.'], 404);
        }

        [$headers, $rows] = $this->readFile($filePath);
        $headers = $this->normalizeHeaders($headers);

        $existing = $this->buildExistingIndex($orgId);

        $imported = 0;
        $skipped = 0;
        $failed = 0;
        $results = [];

        foreach ($rows as $idx => $raw) {
            $rowNumber = $idx + 2;
            $candidateData = $this->mapRow($raw, $headers, $mapping);
            $check = $this->validateAndClassifyRow($candidateData, $existing);

            if ($check['status'] === 'duplicate') {
                $skipped++;
                $results[] = [
                    'row_number' => $rowNumber,
                    'status' => 'skipped_duplicate',
                    'reasons' => $check['reasons'],
                ];
                continue;
            }

            if ($check['status'] === 'invalid') {
                $failed++;
                $results[] = [
                    'row_number' => $rowNumber,
                    'status' => 'failed',
                    'reasons' => $check['reasons'],
                ];
                continue;
            }

            try {
                $candidate = Candidate::create(array_filter([
                    'tenant_id' => $orgId,
                    'first_name' => $candidateData['first_name'] ?? null,
                    'last_name' => $candidateData['last_name'] ?? null,
                    'name' => $candidateData['name'] ?? null,
                    'email' => $candidateData['email'] ?? null,
                    'phone' => $candidateData['phone'] ?? null,
                    'specialty' => $candidateData['specialty'] ?? null,
                    'license_type' => $candidateData['license_type'] ?? null,
                    'years_experience' => $candidateData['years_experience'] ?? null,
                    'city' => $candidateData['city'] ?? null,
                    'state' => $candidateData['state'] ?? null,
                    'source' => $candidateData['source'] ?? null,
                    'notes' => $candidateData['notes'] ?? null,
                ], fn ($v) => $v !== null && $v !== ''));

                CandidatePipeline::query()->firstOrCreate([
                    'tenant_id' => $orgId,
                    'candidate_id' => (int) $candidate->id,
                ], [
                    'stage' => 'new',
                ]);

                $imported++;

                if (!empty($candidateData['email'])) {
                    $existing['emails'][strtolower(trim((string) $candidateData['email']))] = true;
                }
                if (!empty($candidateData['phone_digits'])) {
                    $existing['phones'][$candidateData['phone_digits']] = true;
                }

                $results[] = [
                    'row_number' => $rowNumber,
                    'status' => 'imported',
                    'candidate_id' => $candidate->id,
                ];
            } catch (\Throwable $e) {
                $failed++;
                $results[] = [
                    'row_number' => $rowNumber,
                    'status' => 'failed',
                    'reasons' => ['Failed to import row.'],
                ];
            }
        }

        return response()->api([
            'total_rows' => count($rows),
            'imported' => $imported,
            'skipped_duplicates' => $skipped,
            'failed' => $failed,
            'results' => $results,
        ]);
    }

    private function resolveUploadPath(int $orgId, string $uploadId): ?string
    {
        $disk = Storage::disk('local');
        foreach (['csv', 'txt', 'xlsx'] as $ext) {
            $path = "private/tenants/{$orgId}/imports/candidates/{$uploadId}.{$ext}";
            if ($disk->exists($path)) {
                return $disk->path($path);
            }
        }

        return null;
    }

    private function normalizeHeaders(array $headers): array
    {
        return array_map(function ($h) {
            $h = trim((string) $h);
            return $h === '' ? 'Column' : $h;
        }, $headers);
    }

    /**
     * @return array{0: array<int, string>, 1: array<int, array<string, mixed>>}
     */
    private function readFile(string $absolutePath): array
    {
        $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        if ($ext === 'xlsx') {
            $spreadsheet = IOFactory::load($absolutePath);
            $sheet = $spreadsheet->getActiveSheet();
            $matrix = $sheet->toArray(null, true, true, true);

            if (count($matrix) === 0) {
                return [[], []];
            }

            $firstRow = array_shift($matrix);
            $headers = array_values($firstRow);
            $headers = array_map(fn ($h) => trim((string) $h), $headers);

            $rows = [];
            foreach ($matrix as $row) {
                $rows[] = array_combine($headers, array_values($row)) ?: [];
            }

            return [$headers, $rows];
        }

        $handle = fopen($absolutePath, 'r');
        if ($handle === false) {
            return [[], []];
        }

        $headers = [];
        $rows = [];

        $first = true;
        while (($data = fgetcsv($handle)) !== false) {
            if ($first) {
                $headers = array_map(fn ($h) => trim((string) $h), $data);
                $first = false;
                continue;
            }

            $rowAssoc = [];
            foreach ($headers as $idx => $header) {
                $rowAssoc[$header] = $data[$idx] ?? null;
            }
            $rows[] = $rowAssoc;
        }

        fclose($handle);

        return [$headers, $rows];
    }

    private function mapRow(array $rawRow, array $headers, array $mapping): array
    {
        $out = [];

        foreach (self::SUPPORTED_FIELDS as $field) {
            $mappedHeader = $mapping[$field] ?? null;
            if (!$mappedHeader) {
                continue;
            }

            $value = $rawRow[$mappedHeader] ?? null;
            if (is_string($value)) {
                $value = trim($value);
            }

            $out[$field] = $value;
        }

        $email = $out['email'] ?? null;
        if ($email !== null && $email !== '') {
            $out['email'] = strtolower(trim((string) $email));
        }

        $phone = $out['phone'] ?? null;
        if ($phone !== null && $phone !== '') {
            $phoneStr = trim((string) $phone);
            $digits = preg_replace('/\D+/', '', $phoneStr);
            $out['phone'] = $phoneStr;
            $out['phone_digits'] = $digits ?: null;
        } else {
            $out['phone_digits'] = null;
        }

        $full = trim((string) ($out['full_name'] ?? ''));
        $first = trim((string) ($out['first_name'] ?? ''));
        $last = trim((string) ($out['last_name'] ?? ''));

        if ($first === '' && $last === '' && $full !== '') {
            $parts = preg_split('/\s+/', $full) ?: [];
            $first = (string) array_shift($parts);
            $last = trim(implode(' ', $parts));
            $out['first_name'] = $first !== '' ? $first : null;
            $out['last_name'] = $last !== '' ? $last : null;
        }

        $name = trim(($out['first_name'] ?? '') . ' ' . ($out['last_name'] ?? ''));
        if ($name === '' && $full !== '') {
            $name = $full;
        }
        $out['name'] = $name !== '' ? $name : null;

        if (array_key_exists('years_experience', $out)) {
            $ye = $out['years_experience'];
            if (is_string($ye)) {
                $ye = trim($ye);
            }
            if ($ye === '' || $ye === null) {
                $out['years_experience'] = null;
            } elseif (is_numeric($ye)) {
                $out['years_experience'] = (int) $ye;
            } else {
                $out['years_experience'] = null;
            }
        }

        return $out;
    }

    private function validateAndClassifyRow(array $candidateData, array $existing): array
    {
        $reasons = [];

        $first = trim((string) ($candidateData['first_name'] ?? ''));
        $name = trim((string) ($candidateData['name'] ?? ''));
        if ($first === '' && $name === '') {
            $reasons[] = 'Missing name (first name or full name is required).';
        }

        $email = trim((string) ($candidateData['email'] ?? ''));
        $phoneDigits = (string) ($candidateData['phone_digits'] ?? '');

        if ($email === '' && $phoneDigits === '') {
            $reasons[] = 'Missing contact (email or phone is required).';
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $reasons[] = 'Invalid email.';
        }

        if (count($reasons) > 0) {
            return [
                'status' => 'invalid',
                'reasons' => $reasons,
                'duplicate_key' => null,
            ];
        }

        $dupKey = null;
        if ($email !== '' && !empty($existing['emails'][$email])) {
            $dupKey = 'email';
        }
        if (!$dupKey && $phoneDigits !== '' && !empty($existing['phones'][$phoneDigits])) {
            $dupKey = 'phone';
        }

        if ($dupKey) {
            return [
                'status' => 'duplicate',
                'reasons' => ['Likely duplicate detected.'],
                'duplicate_key' => $dupKey,
            ];
        }

        return [
            'status' => 'valid',
            'reasons' => [],
            'duplicate_key' => null,
        ];
    }

    private function buildExistingIndex(int $orgId): array
    {
        $emails = [];
        $phones = [];

        Candidate::query()
            ->where('tenant_id', $orgId)
            ->select(['email', 'phone'])
            ->orderBy('id')
            ->chunk(500, function ($chunk) use (&$emails, &$phones) {
                foreach ($chunk as $c) {
                    $email = strtolower(trim((string) ($c->email ?? '')));
                    if ($email !== '') {
                        $emails[$email] = true;
                    }

                    $phone = (string) ($c->phone ?? '');
                    $digits = preg_replace('/\D+/', '', $phone);
                    if ($digits) {
                        $phones[$digits] = true;
                    }
                }
            });

        return [
            'emails' => $emails,
            'phones' => $phones,
        ];
    }

    private function summarize(array $previewRows): array
    {
        $summary = [
            'total' => count($previewRows),
            'valid' => 0,
            'duplicates' => 0,
            'invalid' => 0,
        ];

        foreach ($previewRows as $r) {
            if (($r['status'] ?? '') === 'valid') $summary['valid']++;
            if (($r['status'] ?? '') === 'duplicate') $summary['duplicates']++;
            if (($r['status'] ?? '') === 'invalid') $summary['invalid']++;
        }

        return $summary;
    }

    private function csvEscape(string $value): string
    {
        $v = str_replace('"', '""', $value);
        return '"' . $v . '"';
    }
}
