<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Credential>
 */
class CredentialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $issueDate = fake()->dateTimeBetween('-2 years', 'now');
        
        // Create mixed expiry dates: some expired, some expiring soon, some active
        $expiryType = fake()->randomElement(['expired', 'expiring_soon', 'active', 'active']);
        
        $expiryDate = match ($expiryType) {
            'expired' => fake()->dateTimeBetween('-1 year', '-1 day'),
            'expiring_soon' => fake()->dateTimeBetween('now', '+30 days'),
            default => fake()->dateTimeBetween('+31 days', '+2 years'),
        };
        
        $daysUntilExpiry = (int) (new \DateTime($expiryDate->format('Y-m-d')))->diff(new \DateTime())->format('%r%a');
        
        $status = match (true) {
            $daysUntilExpiry < 0 => 'expired',
            $daysUntilExpiry <= 30 => 'expiring_soon',
            default => 'active',
        };

        $credentialTypes = [
            'Provincial Nursing License',
            'Provincial LPN License',
            'CPR Certification',
            'ACLS Certification',
            'Criminal Background Check',
            'Vulnerable Sector Check',
            'Medical Clearance',
            'Immunization Record',
            'TB Test',
            'Education Verification',
            'Employment Verification',
        ];

        $positions = [
            'Registered Nurse (RN)',
            'Licensed Practical Nurse (LPN)',
            'Personal Support Worker (PSW)',
            'Health Care Aide (HCA)',
            'Nurse Practitioner (NP)',
            'Travel Nurse',
        ];

        $provinces = [
            'Alberta',
            'Saskatchewan',
            'Manitoba',
            'Nova Scotia',
            'Prince Edward Island',
            'New Brunswick',
            'Newfoundland & Labrador',
            'Ontario',
            'British Columbia',
        ];

        $specialties = [
            'Medical-Surgical',
            'Intensive Care Unit (ICU)',
            'Emergency',
            'Long Term Care (LTC)',
            'Cardiovascular',
            'Pediatrics',
            'Mental Health',
            'Operating Room',
            'Labor & Delivery',
        ];

        return [
            'user_id' => User::factory(),
            'candidate_name' => fake()->name(),
            'position' => fake()->randomElement($positions),
            'specialty' => fake()->optional(0.7)->randomElement($specialties),
            'credential_type' => fake()->randomElement($credentialTypes),
            'issue_date' => $issueDate->format('Y-m-d'),
            'expiry_date' => $expiryDate->format('Y-m-d'),
            'email' => fake()->unique()->safeEmail(),
            'province' => fake()->optional(0.8)->randomElement($provinces),
            'status' => $status,
        ];
    }
}
        $specialties = [
            'Medical-Surgical',
            'Intensive Care Unit (ICU)',
            'Emergency',
            'Long Term Care (LTC)',
            'Cardiovascular',
            'Pediatrics',
            'Mental Health',
            'Operating Room',
            'Labor & Delivery',
        ];

        return [
            'user_id' => User::factory(),
            'candidate_name' => fake()->name(),
            'position' => fake()->randomElement($positions),
            'specialty' => fake()->optional(0.7)->randomElement($specialties),
            'credential_type' => fake()->randomElement($credentialTypes),
            'issue_date' => $issueDate->format('Y-m-d'),
            'expiry_date' => $expiryDate->format('Y-m-d'),
            'email' => fake()->unique()->safeEmail(),
            'province' => fake()->optional(0.8)->randomElement($provinces),
            'status' => $status,
        ];
    }
}
