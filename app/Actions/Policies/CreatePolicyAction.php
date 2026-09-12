<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\Concerns\GeneratesUniqueSlug;
use App\Enums\PolicyType;
use App\Models\Policy;
use App\Models\PolicyInsured;
use App\Models\PolicyMedicalDetails;
use App\Models\User;
use App\Support\Tenancy\OrganizationContext;
use Illuminate\Support\Facades\DB;

final class CreatePolicyAction
{
    use GeneratesUniqueSlug;

    public function __construct(
        private readonly OrganizationContext $organizationContext,
    ) {}

    /**
     * @param  array{policy_number?: string|null, class: string, subclass: string, type: string, client_id: int, carrier_id: int, agent_id?: int|null, effective_date: string, expiry_date: string, premium_amount: string, discount_amount?: string|null, status: string, source: string, medical: array{coverage_scope: string, class_tier: string, co_insurance: bool, co_insurance_share: string|null, guaranteed_renewable: bool, insured_full_name: string|null, insured_date_of_birth: string|null, insured_gender: string|null, insured_smoker: bool|null, insured_medical_history: string|null}, insureds?: array<int, array{full_name: string, relationship: string, date_of_birth: string, gender: string|null, medical_notes: string|null}>}  $attributes
     *
     * @throws \Throwable
     */
    public function handle(User $user, array $attributes): Policy
    {
        return DB::transaction(function () use ($user, $attributes): Policy {
            $organizationId = $this->organizationContext->id();

            $policyNumber = ! empty($attributes['policy_number'])
                ? $attributes['policy_number']
                : $this->generatePolicyNumber($organizationId);

            $slug = $this->generateUniqueSlug(Policy::class, $policyNumber, $organizationId);

            /** @var Policy $policy */
            $policy = Policy::query()->create([
                'organization_id' => $organizationId,
                'slug' => $slug,
                'policy_number' => $policyNumber,
                'class' => $attributes['class'],
                'subclass' => $attributes['subclass'],
                'type' => $attributes['type'],
                'client_id' => $attributes['client_id'],
                'carrier_id' => $attributes['carrier_id'],
                'agent_id' => $attributes['agent_id'] ?? null,
                'effective_date' => $attributes['effective_date'],
                'expiry_date' => $attributes['expiry_date'],
                'premium_amount' => $attributes['premium_amount'],
                'discount_amount' => $attributes['discount_amount'] ?? 0,
                'status' => $attributes['status'],
                'source' => $attributes['source'],
                'created_by' => $user->id,
            ]);

            $this->createMedicalDetails($policy, $attributes);

            return $policy;
        });
    }

    /**
     * @param  array{medical: array{coverage_scope: string, class_tier: string, co_insurance: bool, co_insurance_share: string|null, guaranteed_renewable: bool, insured_full_name: string|null, insured_date_of_birth: string|null, insured_gender: string|null, insured_smoker: bool|null, insured_medical_history: string|null}, insureds?: array<int, array{full_name: string, relationship: string, date_of_birth: string, gender: string|null, medical_notes: string|null}>}  $attributes
     */
    private function createMedicalDetails(Policy $policy, array $attributes): void
    {
        $medical = $attributes['medical'];

        PolicyMedicalDetails::query()->create([
            'policy_id' => $policy->id,
            'coverage_scope' => $medical['coverage_scope'],
            'class_tier' => $medical['class_tier'],
            'co_insurance' => $medical['co_insurance'],
            'co_insurance_share' => $medical['co_insurance_share'] ?? null,
            'guaranteed_renewable' => $medical['guaranteed_renewable'],
            'insured_full_name' => $medical['insured_full_name'] ?? null,
            'insured_date_of_birth' => $medical['insured_date_of_birth'] ?? null,
            'insured_gender' => $medical['insured_gender'] ?? null,
            'insured_smoker' => $medical['insured_smoker'] ?? null,
            'insured_medical_history' => $medical['insured_medical_history'] ?? null,
        ]);

        if ($policy->type === PolicyType::Group) {
            foreach ($attributes['insureds'] ?? [] as $index => $insured) {
                PolicyInsured::query()->create([
                    'policy_id' => $policy->id,
                    'member_code' => $this->generateMemberCode($index + 1),
                    'full_name' => $insured['full_name'],
                    'relationship' => $insured['relationship'],
                    'date_of_birth' => $insured['date_of_birth'],
                    'gender' => $insured['gender'] ?? null,
                    'medical_notes' => $insured['medical_notes'] ?? null,
                    'status' => 'Active',
                ]);
            }
        }
    }

    private function generatePolicyNumber(int $organizationId): string
    {
        $max = Policy::query()
            ->where('organization_id', $organizationId)
            ->where('policy_number', 'like', 'POL-%')
            ->pluck('policy_number')
            ->map(fn (string $policyNumber): int => (int) str_replace('POL-', '', $policyNumber))
            ->max() ?? 0;

        return 'POL-'.str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }

    private function generateMemberCode(int $sequence): string
    {
        return 'MBR-'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }
}
