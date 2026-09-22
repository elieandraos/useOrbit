<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\Enums\PolicyType;
use App\Models\Policy;
use App\Models\PolicyInsured;
use App\Models\PolicyMedicalDetails;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreatePolicyMedicalAction
{
    public function __construct(
        private readonly CreatePolicyAction $createPolicyAction,
    ) {}

    /**
     * @param  array{policy_number?: string|null, class: string, subclass: string, type: string, client_id: int, carrier_id: int, agent_id?: int|null, effective_date: string, expiry_date: string, premium_amount: string, discount_amount?: string|null, status: string, source: string, medical: array{coverage_scope: string, class_tier: string, co_insurance: bool, co_insurance_share: string|null, guaranteed_renewable: bool, insured_full_name: string|null, insured_date_of_birth: string|null, insured_gender: string|null, insured_smoker: bool|null, insured_medical_history: string|null}, insureds?: array<int, array{full_name: string, relationship: string, date_of_birth: string, gender: string|null, medical_notes: string|null}>}  $attributes
     *
     * @throws \Throwable
     */
    public function handle(User $user, array $attributes): Policy
    {
        return DB::transaction(function () use ($user, $attributes): Policy {
            $policy = $this->createPolicyAction->handle($user, $attributes);

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

    private function generateMemberCode(int $sequence): string
    {
        return 'MBR-'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }
}
