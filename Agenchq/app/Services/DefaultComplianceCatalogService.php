<?php

namespace App\Services;

use App\Models\CredentialType;
use App\Models\Organization;
use App\Models\Template;
use App\Models\User;

class DefaultComplianceCatalogService
{
    /**
     * Baseline list aligned with common healthcare staffing credentialing practices.
     */
    private const DEFAULT_ITEMS = [
        ['name' => 'RN/LPN License', 'credential_type' => 'Nursing License', 'category' => 'license', 'default_days' => 365, 'description' => 'Active state nursing license matching role and specialty.'],
        ['name' => 'Government ID', 'credential_type' => 'Government ID', 'category' => 'identity', 'default_days' => 3650, 'description' => 'Government-issued photo identification used for verification.'],
        ['name' => 'Work Authorization (I-9)', 'credential_type' => 'Work Authorization', 'category' => 'eligibility', 'default_days' => 3650, 'description' => 'Employment eligibility / I-9 supporting documentation.'],
        ['name' => 'BLS Certification', 'credential_type' => 'BLS', 'category' => 'certification', 'default_days' => 730, 'description' => 'Basic Life Support certification.'],
        ['name' => 'ACLS Certification', 'credential_type' => 'ACLS', 'category' => 'certification', 'default_days' => 730, 'description' => 'Advanced Cardiac Life Support certification when required.'],
        ['name' => 'PALS Certification (if applicable)', 'credential_type' => 'PALS', 'category' => 'certification', 'default_days' => 730, 'description' => 'Pediatric Advanced Life Support certification when applicable.'],
        ['name' => 'NRP Certification (if applicable)', 'credential_type' => 'NRP', 'category' => 'certification', 'default_days' => 730, 'description' => 'Neonatal Resuscitation Program certification when applicable.'],
        ['name' => 'NIH Stroke Scale (if applicable)', 'credential_type' => 'NIHSS', 'category' => 'certification', 'default_days' => 365, 'description' => 'NIH Stroke Scale certification for stroke-capable units.'],
        ['name' => 'Background Check', 'credential_type' => 'Background Check', 'category' => 'screening', 'default_days' => 365, 'description' => 'Criminal background screening per facility and state policy.'],
        ['name' => 'Drug Screen', 'credential_type' => 'Drug Screen', 'category' => 'screening', 'default_days' => 365, 'description' => 'Pre-employment / annual drug screening.'],
        ['name' => 'TB Screening', 'credential_type' => 'TB Screening', 'category' => 'health', 'default_days' => 365, 'description' => 'Annual TB screening (PPD or QuantiFERON).'],
        ['name' => 'Annual Physical / Fit for Duty', 'credential_type' => 'Physical Exam', 'category' => 'health', 'default_days' => 365, 'description' => 'Provider statement confirming fit for duty and communicable disease clearance.'],
        ['name' => 'N95 Respirator Fit Test', 'credential_type' => 'N95 Fit Test', 'category' => 'safety', 'default_days' => 365, 'description' => 'Annual respirator fit test where required by OSHA respiratory protection programs.'],
        ['name' => 'Influenza Vaccine', 'credential_type' => 'Influenza', 'category' => 'immunization', 'default_days' => 365, 'description' => 'Seasonal flu vaccination or documented declination per policy.'],
        ['name' => 'COVID-19 Vaccination', 'credential_type' => 'COVID-19', 'category' => 'immunization', 'default_days' => 365, 'description' => 'COVID-19 vaccination status and boosters per facility policy.'],
        ['name' => 'MMR Immunization', 'credential_type' => 'MMR', 'category' => 'immunization', 'default_days' => 3650, 'description' => 'Measles, mumps, rubella immunity record or titer.'],
        ['name' => 'Varicella Immunization', 'credential_type' => 'Varicella', 'category' => 'immunization', 'default_days' => 3650, 'description' => 'Varicella immunity record or titer.'],
        ['name' => 'Hepatitis B Immunization', 'credential_type' => 'Hepatitis B', 'category' => 'immunization', 'default_days' => 3650, 'description' => 'Hepatitis B immunity or vaccination series documentation.'],
        ['name' => 'Tdap Immunization', 'credential_type' => 'Tdap', 'category' => 'immunization', 'default_days' => 3650, 'description' => 'Tetanus, diphtheria, pertussis vaccination documentation.'],
        ['name' => 'Professional References', 'credential_type' => 'Professional References', 'category' => 'employment', 'default_days' => 365, 'description' => 'Recent professional references aligned to role requirements.'],
    ];

    public function ensureForOrganization(int $orgId, ?int $seedUserId = null): void
    {
        if ($orgId <= 0) {
            return;
        }

        $orgExists = Organization::query()->whereKey($orgId)->exists();
        if (!$orgExists) {
            return;
        }

        $seedUserId = $seedUserId ?: (int) (User::query()
            ->where('organization_id', $orgId)
            ->orderBy('id')
            ->value('id') ?? 0);

        if ($seedUserId <= 0) {
            return;
        }

        foreach (self::DEFAULT_ITEMS as $item) {
            $typeName = (string) $item['credential_type'];

            CredentialType::query()->updateOrCreate(
                [
                    'tenant_id' => $orgId,
                    'name' => $typeName,
                ],
                [
                    'category' => (string) $item['category'],
                    'requires_expiration' => true,
                    'requires_document' => true,
                ]
            );

            $templateExists = Template::query()
                ->where('organization_id', $orgId)
                ->where('is_active', true)
                ->where('credential_type', $typeName)
                ->exists();

            if ($templateExists) {
                continue;
            }

            Template::query()->create([
                'organization_id' => $orgId,
                'user_id' => $seedUserId,
                'name' => (string) $item['name'],
                'credential_type' => $typeName,
                'position' => 'nurse',
                'default_days' => (int) $item['default_days'],
                'description' => (string) $item['description'],
                'is_active' => true,
            ]);
        }
    }
}
