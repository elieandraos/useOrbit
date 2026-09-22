<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\Enums\PolicyType;
use App\Models\Policy;
use App\Models\PolicyInsured;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UpdatePolicyMedicalAction
{
    public function __construct(
        private readonly UpdatePolicyAction $updatePolicyAction,
    ) {}

    /**
     * @param  array{policy_number?: string|null, class: string, subclass: string, type: string, client_id: int, carrier_id: int, agent_id?: int|null, effective_date: string, expiry_date: string, premium_amount: string, discount_amount?: string|null, status: string, source: string, medical: array{coverage_scope: string, class_tier: string, co_insurance: bool, co_insurance_share: string|null, guaranteed_renewable: bool, insured_full_name: string|null, insured_date_of_birth: string|null, insured_gender: string|null, insured_smoker: bool|null, insured_medical_history: string|null}, insureds?: array<int, array{id?: int|null, full_name: string, relationship: string, date_of_birth: string, gender: string|null, medical_notes: string|null}>}  $attributes
     *
     * @throws \Throwable
     */
    public function handle(User $user, Policy $policy, array $attributes): Policy
    {
        return DB::transaction(function () use ($user, $policy, $attributes): Policy {
            $policy = $this->updatePolicyAction->handle($user, $policy, $attributes);

            $this->updateMedicalDetails($policy, $attributes);

            return $policy->fresh();
        });
    }

    /**
     * @param  array{medical: array{coverage_scope: string, class_tier: string, co_insurance: bool, co_insurance_share: string|null, guaranteed_renewable: bool, insured_full_name: string|null, insured_date_of_birth: string|null, insured_gender: string|null, insured_smoker: bool|null, insured_medical_history: string|null}, insureds?: array<int, array{id?: int|null, full_name: string, relationship: string, date_of_birth: string, gender: string|null, medical_notes: string|null}>}  $attributes
     */
    private function updateMedicalDetails(Policy $policy, array $attributes): void
    {
        $medical = $attributes['medical'];

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

        if ($policy->type === PolicyType::Group) {
            $this->syncInsureds($policy, $attributes['insureds'] ?? []);
        } else {
            $policy->insureds()->delete();
        }
    }

    /**
     * @param  array<int, array{id?: int|null, full_name: string, relationship: string, date_of_birth: string, gender: string|null, medical_notes: string|null}>  $insureds
     */
    private function syncInsureds(Policy $policy, array $insureds): void
    {
        $existing = $policy->insureds()->get()->keyBy('id');

        $nextSequence = $existing
            ->pluck('member_code')
            ->map(fn (string $memberCode): int => (int) str_replace('MBR-', '', $memberCode))
            ->max() ?? 0;

        $submittedIds = [];

        foreach ($insureds as $insured) {
            $fields = [
                'full_name' => $insured['full_name'],
                'relationship' => $insured['relationship'],
                'date_of_birth' => $insured['date_of_birth'],
                'gender' => $insured['gender'] ?? null,
                'medical_notes' => $insured['medical_notes'] ?? null,
            ];

            if (! empty($insured['id'])) {
                $existing[$insured['id']]->update($fields);
                $submittedIds[] = $insured['id'];

                continue;
            }

            $nextSequence++;

            $created = PolicyInsured::query()->create([
                ...$fields,
                'policy_id' => $policy->id,
                'member_code' => 'MBR-'.str_pad((string) $nextSequence, 3, '0', STR_PAD_LEFT),
                'status' => 'Active',
            ]);

            $submittedIds[] = $created->id;
        }

        $policy->insureds()->whereNotIn('id', $submittedIds)->delete();
    }
}
