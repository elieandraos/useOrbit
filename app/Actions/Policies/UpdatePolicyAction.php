<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\Concerns\GeneratesUniqueSlug;
use App\Enums\PolicyType;
use App\Models\Policy;
use App\Models\PolicyInsured;
use App\Models\User;
use App\Support\Tenancy\OrganizationContext;
use Illuminate\Support\Facades\DB;

final class UpdatePolicyAction
{
    use GeneratesUniqueSlug;

    public function __construct(
        private readonly OrganizationContext $organizationContext,
    ) {}

    /**
     * @param  array{policy_number?: string|null, class: string, subclass: string, type: string, client_id: int, carrier_id: int, agent_id?: int|null, effective_date: string, expiry_date: string, premium_amount: string, discount_amount?: string|null, status: string, source: string, medical: array{coverage_scope: string, class_tier: string, co_insurance: bool, co_insurance_share: string|null, guaranteed_renewable: bool, insured_full_name: string|null, insured_date_of_birth: string|null, insured_gender: string|null, insured_smoker: bool|null, insured_medical_history: string|null}, insureds?: array<int, array{id?: int|null, full_name: string, relationship: string, date_of_birth: string, gender: string|null, medical_notes: string|null}>}  $attributes
     *
     * @throws \Throwable
     */
    public function handle(User $user, Policy $policy, array $attributes): Policy
    {
        return DB::transaction(function () use ($user, $policy, $attributes): Policy {
            $policyNumber = ! empty($attributes['policy_number'])
                ? $attributes['policy_number']
                : $policy->policy_number;

            $numberChanged = $policyNumber !== $policy->policy_number;

            $slug = $numberChanged
                ? $this->generateUniqueSlug(Policy::class, $policyNumber, $this->organizationContext->id(), $policy->id)
                : $policy->slug;

            $policy->update([
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
                'updated_by' => $user->id,
            ]);

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
