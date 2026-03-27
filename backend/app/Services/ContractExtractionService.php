<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractTerm;
use App\Models\ContractRateLine;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ContractExtractionService
{
    /**
     * Extract terms from a contract document.
     *
     * @param Contract $contract
     * @return array{terms: ContractTerm, rate_lines: array}
     */
    public function extract(Contract $contract): array
    {
        Log::info('[CONTRACT EXTRACTION] Starting extraction', [
            'contract_id' => $contract->id,
            'document_type' => $contract->document_type,
        ]);

        // Update status to processing
        $contract->update(['status' => Contract::STATUS_PROCESSING]);

        try {
            // Get extracted text from the document
            $text = $this->extractTextFromDocument($contract);

            // Extract structured terms
            $extractedTerms = $this->extractTerms($text);
            $extractedRateLines = $this->extractRateLines($text);

            // Create contract terms record
            $terms = ContractTerm::create([
                'contract_id' => $contract->id,
                ...$extractedTerms['values'],
                'confidence_json' => $extractedTerms['confidence'],
                'source_spans_json' => $extractedTerms['sources'],
                'review_status' => ContractTerm::REVIEW_PENDING,
            ]);

            // Create rate lines
            $rateLines = [];
            foreach ($extractedRateLines as $rateLineData) {
                $rateLines[] = ContractRateLine::create([
                    'contract_id' => $contract->id,
                    ...$rateLineData,
                    'review_status' => ContractRateLine::REVIEW_PENDING,
                ]);
            }

            // Update contract status
            $contract->update([
                'status' => Contract::STATUS_PROCESSED,
                'extracted_text' => $text,
            ]);

            Log::info('[CONTRACT EXTRACTION] Extraction complete', [
                'contract_id' => $contract->id,
                'terms_id' => $terms->id,
                'rate_lines_count' => count($rateLines),
                'text_length' => strlen($text),
            ]);

            return [
                'terms' => $terms,
                'rate_lines' => $rateLines,
            ];
        } catch (\Exception $e) {
            Log::error('[CONTRACT EXTRACTION] Extraction failed', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
            ]);

            $contract->update(['status' => Contract::STATUS_UPLOADED]);
            throw $e;
        }
    }

    /**
     * Extract text from document file (PDF, DOC, DOCX).
     */
    protected function extractTextFromDocument(Contract $contract): string
    {
        if (!$contract->file_path) {
            return '';
        }

        $extension = strtolower(pathinfo($contract->file_path, PATHINFO_EXTENSION));
        
        // Get file content from storage
        $content = Storage::disk('private_assets')->get($contract->file_path);
        if (!$content) {
            Log::warning('[CONTRACT EXTRACTION] File not found', [
                'path' => $contract->file_path,
            ]);
            return '';
        }

        // Save to temp file for parsing
        $tempPath = tempnam(sys_get_temp_dir(), 'contract_') . '.' . $extension;
        file_put_contents($tempPath, $content);

        $text = '';

        try {
            if ($extension === 'pdf') {
                $text = $this->extractFromPdf($tempPath);
            } elseif (in_array($extension, ['doc', 'docx'])) {
                $text = $this->extractFromWord($tempPath);
            }
        } catch (\Exception $e) {
            Log::error('[CONTRACT EXTRACTION] Text extraction failed', [
                'error' => $e->getMessage(),
                'extension' => $extension,
            ]);
        }

        // Clean up temp file
        if (file_exists($tempPath)) {
            unlink($tempPath);
        }

        return $text;
    }

    /**
     * Extract text from PDF using pdftotext command line tool.
     * Falls back to basic content extraction if pdftotext is not available.
     */
    protected function extractFromPdf(string $path): string
    {
        // Try pdftotext command first (most reliable)
        if ($this->commandExists('pdftotext')) {
            $output = [];
            $returnCode = 0;
            exec("pdftotext -layout " . escapeshellarg($path) . " - 2>&1", $output, $returnCode);
            
            if ($returnCode === 0 && !empty($output)) {
                $text = implode("\n", $output);
                $text = preg_replace('/\s+/', ' ', $text);
                $text = trim($text);
                
                Log::info('[CONTRACT EXTRACTION] PDF text extracted via pdftotext', [
                    'length' => strlen($text),
                ]);
                
                return $text;
            }
        }

        // Fallback: try to extract text content directly from PDF
        // PDFs contain streams that may have readable text
        $content = file_get_contents($path);
        
        // Try to extract text between stream markers
        $text = '';
        if (preg_match_all('/stream\s*\n(.*?)\nendstream/s', $content, $matches)) {
            foreach ($matches[1] as $stream) {
                // Try to decode if it looks like base64 or compressed
                $decoded = @gzuncompress($stream);
                if ($decoded !== false) {
                    $text .= $decoded . ' ';
                } else {
                    // Just use raw content, filter printable characters
                    $text .= preg_replace('/[^\x20-\x7E\n]/', '', $stream) . ' ';
                }
            }
        }
        
        // Clean up
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        Log::info('[CONTRACT EXTRACTION] PDF text extracted via fallback', [
            'length' => strlen($text),
        ]);
        
        return $text;
    }

    /**
     * Check if a command exists on the system.
     */
    protected function commandExists(string $command): bool
    {
        $result = shell_exec("which $command 2>/dev/null");
        return !empty($result);
    }

    /**
     * Extract text from Word document.
     */
    protected function extractFromWord(string $path): string
    {
        // DOCX is a ZIP file with XML content
        if (pathinfo($path, PATHINFO_EXTENSION) === 'docx') {
            $zip = new \ZipArchive();
            if ($zip->open($path) === true) {
                $content = $zip->getFromName('word/document.xml');
                $zip->close();
                
                if ($content) {
                    // Strip XML tags and get text
                    $text = strip_tags($content);
                    $text = preg_replace('/\s+/', ' ', $text);
                    return trim($text);
                }
            }
        }
        
        // For older .doc files, try antiword if available
        if ($this->commandExists('antiword')) {
            $output = [];
            exec("antiword " . escapeshellarg($path) . " 2>&1", $output);
            $text = implode("\n", $output);
            return trim($text);
        }
        
        Log::warning('[CONTRACT EXTRACTION] DOC format not fully supported');
        return '';
    }

    /**
     * Extract structured terms from document text.
     * Stub implementation - returns placeholder values with low confidence.
     */
    protected function extractTerms(string $text): array
    {
        // TODO: Implement actual AI-based extraction
        // This is a scaffold that returns placeholder values
        
        $values = [
            'payment_terms_days' => null,
            'invoice_frequency' => null,
            'currency' => 'USD',
            'bill_rate_type' => null,
            'bill_rate_amount' => null,
            'pay_rate_amount' => null,
            'markup_percent' => null,
            'overtime_multiplier' => null,
            'holiday_multiplier' => null,
            'timesheet_required' => null,
            'expense_allowed' => null,
            'minimum_bill_hours' => null,
        ];

        // Stub confidence scores (0.0 - 1.0)
        // Low confidence indicates extraction needs review
        $confidence = [];
        $sources = [];

        foreach (ContractTerm::getExtractableFields() as $field) {
            $confidence[$field] = 0.0; // No confidence for stub data
            $sources[$field] = null;
        }

        // Try to extract some basic patterns (very naive implementation)
        $this->extractBasicPatterns($text, $values, $confidence, $sources);

        return [
            'values' => $values,
            'confidence' => $confidence,
            'sources' => $sources,
        ];
    }

    /**
     * Extract rate lines from document text.
     * Stub implementation - returns empty array.
     */
    protected function extractRateLines(string $text): array
    {
        // TODO: Implement actual rate table extraction
        // This would parse rate tables from the document
        
        return [];
    }

    /**
     * Very naive pattern extraction for basic terms.
     * This is a placeholder - real extraction would use AI/ML.
     */
    protected function extractBasicPatterns(
        string $text,
        array &$values,
        array &$confidence,
        array &$sources
    ): void {
        // Payment terms pattern (e.g., "Net 30", "payment within 30 days")
        if (preg_match('/net\s*(\d+)/i', $text, $matches)) {
            $values['payment_terms_days'] = (int) $matches[1];
            $confidence['payment_terms_days'] = 0.7;
            $sources['payment_terms_days'] = [
                'text' => $matches[0],
                'page' => null,
            ];
        } elseif (preg_match('/payment\s+(?:due|within)\s+(\d+)\s+days?/i', $text, $matches)) {
            $values['payment_terms_days'] = (int) $matches[1];
            $confidence['payment_terms_days'] = 0.6;
            $sources['payment_terms_days'] = [
                'text' => $matches[0],
                'page' => null,
            ];
        }

        // Invoice frequency pattern
        if (preg_match('/(weekly|biweekly|monthly)\s*invoice/i', $text, $matches)) {
            $values['invoice_frequency'] = strtolower($matches[1]);
            $confidence['invoice_frequency'] = 0.6;
            $sources['invoice_frequency'] = [
                'text' => $matches[0],
                'page' => null,
            ];
        } elseif (preg_match('/invoices?\s+(?:are\s+)?(?:submitted\s+)?(weekly|biweekly|monthly)/i', $text, $matches)) {
            $values['invoice_frequency'] = strtolower($matches[1]);
            $confidence['invoice_frequency'] = 0.5;
            $sources['invoice_frequency'] = [
                'text' => $matches[0],
                'page' => null,
            ];
        }

        // Overtime multiplier pattern (e.g., "1.5x", "time and a half")
        if (preg_match('/(\d+\.?\d*)\s*x?\s*(?:for\s+)?overtime/i', $text, $matches)) {
            $values['overtime_multiplier'] = (float) $matches[1];
            $confidence['overtime_multiplier'] = 0.5;
            $sources['overtime_multiplier'] = [
                'text' => $matches[0],
                'page' => null,
            ];
        } elseif (stripos($text, 'time and a half') !== false) {
            $values['overtime_multiplier'] = 1.5;
            $confidence['overtime_multiplier'] = 0.8;
            $sources['overtime_multiplier'] = [
                'text' => 'time and a half',
                'page' => null,
            ];
        } elseif (stripos($text, 'one and a half times') !== false) {
            $values['overtime_multiplier'] = 1.5;
            $confidence['overtime_multiplier'] = 0.7;
            $sources['overtime_multiplier'] = [
                'text' => 'one and a half times',
                'page' => null,
            ];
        }

        // Holiday multiplier pattern
        if (preg_match('/(\d+\.?\d*)\s*x?\s*(?:for\s+)?holiday/i', $text, $matches)) {
            $values['holiday_multiplier'] = (float) $matches[1];
            $confidence['holiday_multiplier'] = 0.5;
            $sources['holiday_multiplier'] = [
                'text' => $matches[0],
                'page' => null,
            ];
        } elseif (stripos($text, 'double time') !== false) {
            $values['holiday_multiplier'] = 2.0;
            $confidence['holiday_multiplier'] = 0.7;
            $sources['holiday_multiplier'] = [
                'text' => 'double time',
                'page' => null,
            ];
        }

        // Currency pattern
        if (preg_match('/\b(USD|EUR|GBP|CAD)\b/', $text, $matches)) {
            $values['currency'] = $matches[1];
            $confidence['currency'] = 0.9;
            $sources['currency'] = [
                'text' => $matches[0],
                'page' => null,
            ];
        }

        // Bill rate pattern (e.g., "$50 per hour", "$50.00/hr")
        if (preg_match('/\$?(\d+\.?\d*)\s*(?:per\s+hour|\/hr|hourly)/i', $text, $matches)) {
            $values['bill_rate_amount'] = (float) $matches[1];
            $values['bill_rate_type'] = 'hourly';
            $confidence['bill_rate_amount'] = 0.6;
            $confidence['bill_rate_type'] = 0.6;
            $sources['bill_rate_amount'] = [
                'text' => $matches[0],
                'page' => null,
            ];
            $sources['bill_rate_type'] = [
                'text' => $matches[0],
                'page' => null,
            ];
        }

        // Pay rate pattern
        if (preg_match('/pay\s+rate[:\s]+\$?(\d+\.?\d*)/i', $text, $matches)) {
            $values['pay_rate_amount'] = (float) $matches[1];
            $confidence['pay_rate_amount'] = 0.5;
            $sources['pay_rate_amount'] = [
                'text' => $matches[0],
                'page' => null,
            ];
        }

        // Markup percentage pattern
        if (preg_match('/markup\s+(?:of\s+)?(\d+\.?\d*)\s*%/i', $text, $matches)) {
            $values['markup_percent'] = (float) $matches[1];
            $confidence['markup_percent'] = 0.5;
            $sources['markup_percent'] = [
                'text' => $matches[0],
                'page' => null,
            ];
        }

        // Timesheet required pattern
        if (preg_match('/timesheets?\s+(?:are\s+)?required/i', $text, $matches)) {
            $values['timesheet_required'] = true;
            $confidence['timesheet_required'] = 0.7;
            $sources['timesheet_required'] = [
                'text' => $matches[0],
                'page' => null,
            ];
        } elseif (preg_match('/no\s+timesheets?\s+required/i', $text, $matches)) {
            $values['timesheet_required'] = false;
            $confidence['timesheet_required'] = 0.7;
            $sources['timesheet_required'] = [
                'text' => $matches[0],
                'page' => null,
            ];
        }

        // Expense allowed pattern
        if (preg_match('/expenses?\s+(?:are\s+)?(?:allowed|permitted|reimbursable)/i', $text, $matches)) {
            $values['expense_allowed'] = true;
            $confidence['expense_allowed'] = 0.6;
            $sources['expense_allowed'] = [
                'text' => $matches[0],
                'page' => null,
            ];
        } elseif (preg_match('/expenses?\s+(?:are\s+)?not\s+(?:allowed|permitted|reimbursable)/i', $text, $matches)) {
            $values['expense_allowed'] = false;
            $confidence['expense_allowed'] = 0.6;
            $sources['expense_allowed'] = [
                'text' => $matches[0],
                'page' => null,
            ];
        }

        // Minimum bill hours pattern
        if (preg_match('/minimum\s+(?:bill(?:ing)?|shift)\s+(?:hours?|hrs?)[:\s]+(\d+\.?\d*)/i', $text, $matches)) {
            $values['minimum_bill_hours'] = (float) $matches[1];
            $confidence['minimum_bill_hours'] = 0.5;
            $sources['minimum_bill_hours'] = [
                'text' => $matches[0],
                'page' => null,
            ];
        } elseif (preg_match('/(\d+\.?\d*)\s*(?:hour|hr)s?\s+minimum/i', $text, $matches)) {
            $values['minimum_bill_hours'] = (float) $matches[1];
            $confidence['minimum_bill_hours'] = 0.5;
            $sources['minimum_bill_hours'] = [
                'text' => $matches[0],
                'page' => null,
            ];
        }
    }

    /**
     * Map contract terms to billing settings format.
     * Used when applying approved contract terms to billing.
     */
    public function mapTermsToBilling(ContractTerm $terms): array
    {
        return [
            'payment_terms_days' => $terms->getEffectiveValue('payment_terms_days'),
            'invoice_frequency' => $terms->getEffectiveValue('invoice_frequency'),
            'currency' => $terms->currency ?? 'USD',
            'default_bill_rate' => $terms->getEffectiveValue('bill_rate_amount'),
            'default_pay_rate' => $terms->getEffectiveValue('pay_rate_amount'),
            'default_markup_percent' => $terms->getEffectiveValue('markup_percent'),
            'overtime_multiplier' => $terms->getEffectiveValue('overtime_multiplier') ?? 1.5,
            'holiday_multiplier' => $terms->getEffectiveValue('holiday_multiplier') ?? 2.0,
            'timesheet_required' => $terms->getEffectiveValue('timesheet_required') ?? true,
            'expense_allowed' => $terms->getEffectiveValue('expense_allowed') ?? false,
            'minimum_bill_hours' => $terms->getEffectiveValue('minimum_bill_hours') ?? 0,
        ];
    }
}
