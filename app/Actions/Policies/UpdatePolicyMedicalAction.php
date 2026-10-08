<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\Enums\PolicyType;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UpdatePolicyMedicalAction
{
    public function __construct(
        private readonly UpdatePolicyAction $updatePolicyAction,
        private readonly SyncPolicyInsuredsAction $syncPolicyInsuredsAction,
    ) {}

    /**
     * @param  array{policy_number: string, class: string, subclass: string, type: string, client_id: string, carrier_id: string, carrier_branch_id?: string|null, agent_id?: string|null, currency_id: string, effective_date: string, expiry_date: string, premium_amount: string, discount_amount?: string|null, source: string, medical: array{coverage_scope: string, class_tier: string, co_insurance: bool, co_insurance_share: string|null, guaranteed_renewable: string, insured_full_name: string|null, insured_date_of_birth: string|null, insured_gender: string|null, insured_smoker: bool|null, insured_medical_history: string|null}, insureds?: array<int, array{id?: string|null, full_name: string, relationship: string, date_of_birth: string, gender?: string|null, medical_notes?: string|null}>}  $attributes
     *
     * @throws \Throwable
     */
    public function handle(User $user, Policy $policy, array $attributes): Policy
    {
        return DB::transaction(function () use ($user, $policy, $attributes): Policy {
            $policy = $this->updatePolicyAction->handle($user, $policy, $attributes);

            $this->updateMedicalDetails($policy, $attributes['medical']);

            $this->syncPolicyInsuredsAction->handle(
                $policy,
                $policy->type === PolicyType::Group ? $attributes['insureds'] ?? [] : [],
            );

            return $policy->fresh();
        });
    }

    /**
     * @param  array{coverage_scope: string, class_tier: string, co_insurance: bool, co_insurance_share: string|null, guaranteed_renewable: string, insured_full_name: string|null, insured_date_of_birth: string|null, insured_gender: string|null, insured_smoker: bool|null, insured_medical_history: string|null}  $medical
     */
    private function updateMedicalDetails(Policy $policy, array $medical): void
    {
        $policy->medicalDetails->update([
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
    }
}
