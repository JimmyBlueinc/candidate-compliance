<?php

use App\Models\Organization;
use App\Models\Template;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            ['name' => 'RN/LPN License', 'credential_type' => 'Nursing License', 'default_days' => 365],
            ['name' => 'BLS', 'credential_type' => 'BLS', 'default_days' => 730],
            ['name' => 'ACLS', 'credential_type' => 'ACLS', 'default_days' => 730],
            ['name' => 'PALS (if applicable)', 'credential_type' => 'PALS', 'default_days' => 730],
            ['name' => 'NRP (if applicable)', 'credential_type' => 'NRP', 'default_days' => 730],
            ['name' => 'Background Check', 'credential_type' => 'Background Check', 'default_days' => 365],
            ['name' => 'TB Screening', 'credential_type' => 'TB Screening', 'default_days' => 365],
            ['name' => 'MMR Immunization', 'credential_type' => 'MMR', 'default_days' => 3650],
            ['name' => 'Varicella Immunization', 'credential_type' => 'Varicella', 'default_days' => 3650],
            ['name' => 'Hepatitis B', 'credential_type' => 'Hepatitis B', 'default_days' => 3650],
            ['name' => 'Tdap', 'credential_type' => 'Tdap', 'default_days' => 3650],
            ['name' => 'Influenza Vaccine', 'credential_type' => 'Influenza', 'default_days' => 365],
            ['name' => 'N95 Fit Test', 'credential_type' => 'N95 Fit Test', 'default_days' => 365],
            ['name' => 'Drug Screen', 'credential_type' => 'Drug Screen', 'default_days' => 365],
        ];

        $orgs = Organization::query()->get(['id']);

        foreach ($orgs as $org) {
            DB::transaction(function () use ($org, $defaults) {
                $seedUserId = DB::table('users')
                    ->where('organization_id', $org->id)
                    ->orderBy('id')
                    ->value('id');

                if (!$seedUserId) {
                    return;
                }

                foreach ($defaults as $row) {
                    $exists = Template::query()
                        ->where('organization_id', $org->id)
                        ->where('is_active', true)
                        ->where('credential_type', $row['credential_type'])
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    Template::create([
                        'organization_id' => $org->id,
                        'user_id' => $seedUserId,
                        'name' => $row['name'],
                        'credential_type' => $row['credential_type'],
                        'position' => 'nurse',
                        'default_days' => $row['default_days'],
                        'description' => null,
                        'is_active' => true,
                    ]);
                }
            });
        }
    }

    public function down(): void
    {
        Template::query()
            ->where('position', 'nurse')
            ->whereIn('credential_type', [
                'Nursing License',
                'BLS',
                'ACLS',
                'PALS',
                'NRP',
                'Background Check',
                'TB Screening',
                'MMR',
                'Varicella',
                'Hepatitis B',
                'Tdap',
                'Influenza',
                'N95 Fit Test',
                'Drug Screen',
            ])
            ->delete();
    }
};
