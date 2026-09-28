<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\Models\Policy;
use App\Models\PolicyInsured;
use Illuminate\Support\Facades\DB;

final class SyncPolicyInsuredsAction
{
    /**
     * Make the policy's insured members match the submitted list: update submitted existing members,
     * create new ones with the next `MBR-###` member code, and delete members no longer submitted.
     *
     * @param  array<int, array{id?: string|null, full_name: string, relationship: string, date_of_birth: string, gender?: string|null, medical_notes?: string|null}>  $insureds
     *
     * @throws \Throwable
     */
    public function handle(Policy $policy, array $insureds): void
    {
        DB::transaction(function () use ($policy, $insureds): void {
            $existing = $policy->insureds()->get()->keyBy('id');

            $lastSequence = $existing
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

                $lastSequence++;

                $created = PolicyInsured::query()->create([
                    ...$fields,
                    'policy_id' => $policy->id,
                    'member_code' => $this->generateMemberCode($lastSequence),
                    'status' => 'Active',
                ]);

                $submittedIds[] = $created->id;
            }

            $policy->insureds()->whereNotIn('id', $submittedIds)->delete();
        });
    }

    private function generateMemberCode(int $sequence): string
    {
        return 'MBR-'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }
}
