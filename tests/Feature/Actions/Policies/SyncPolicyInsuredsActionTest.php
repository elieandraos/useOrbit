<?php

declare(strict_types=1);

use App\Actions\Policies\SyncPolicyInsuredsAction;
use App\Models\Policy;
use App\Models\PolicyInsured;
use App\Models\User;
use Tests\Support\PolicyPayload;

test('a policy without members gets each submitted insured with sequential member codes from MBR-001', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id, 'type' => 'group']);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(SyncPolicyInsuredsAction::class)->handle($policy, [
        PolicyPayload::insured([
            'full_name' => 'First Member',
            'relationship' => 'Spouse',
            'date_of_birth' => '1990-05-12',
            'medical_notes' => 'Asthma',
        ]),
        PolicyPayload::insured(['full_name' => 'Second Member', 'relationship' => 'Child', 'gender' => null]),
    ]);

    $insureds = $policy->insureds()->orderBy('member_code')->get();
    expect($insureds)->toHaveCount(2)
        ->and($insureds[0]->only(['member_code', 'full_name', 'relationship', 'medical_notes', 'status']))->toBe([
            'member_code' => 'MBR-001',
            'full_name' => 'First Member',
            'relationship' => 'Spouse',
            'medical_notes' => 'Asthma',
            'status' => 'Active',
        ])
        ->and($insureds[0]->date_of_birth->format('Y-m-d'))->toBe('1990-05-12')
        ->and($insureds[1]->member_code)->toBe('MBR-002')
        ->and($insureds[1]->gender)->toBeNull();
});

test('existing members keep their codes while new members continue after the highest code', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id, 'type' => 'group']);
    $first = PolicyInsured::factory()->for($policy)->create(['member_code' => 'MBR-001']);
    $third = PolicyInsured::factory()->for($policy)->create(['member_code' => 'MBR-003']);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(SyncPolicyInsuredsAction::class)->handle($policy, [
        PolicyPayload::insured(['id' => (string) $first->id, 'full_name' => 'Renamed Member']),
        PolicyPayload::insured(['id' => (string) $third->id]),
        PolicyPayload::insured(['full_name' => 'New Member']),
    ]);

    expect($first->fresh()->only(['member_code', 'full_name']))->toBe(['member_code' => 'MBR-001', 'full_name' => 'Renamed Member'])
        ->and($third->fresh()->member_code)->toBe('MBR-003')
        ->and($policy->insureds()->where('full_name', 'New Member')->value('member_code'))->toBe('MBR-004')
        ->and($policy->insureds()->count())->toBe(3);
});

test('members omitted from the submission are deleted', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id, 'type' => 'group']);
    $kept = PolicyInsured::factory()->for($policy)->create(['member_code' => 'MBR-001']);
    $omitted = PolicyInsured::factory()->for($policy)->create(['member_code' => 'MBR-002']);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(SyncPolicyInsuredsAction::class)->handle($policy, [
        PolicyPayload::insured(['id' => (string) $kept->id]),
    ]);

    $this->assertModelExists($kept);
    $this->assertModelMissing($omitted);
});

test('an empty submission deletes every member of the policy and no other policy\'s members', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id, 'type' => 'group']);
    PolicyInsured::factory()->for($policy)->count(2)->create();
    $otherUser = User::factory()->withOrganization()->create();
    $otherPolicy = Policy::factory()->forOrganization($otherUser)->medical()->create(['created_by' => $otherUser->id, 'type' => 'group']);
    $otherPolicyMember = PolicyInsured::factory()->for($otherPolicy)->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    app(SyncPolicyInsuredsAction::class)->handle($policy, []);

    expect($policy->insureds()->count())->toBe(0);
    $this->assertModelExists($otherPolicyMember);
});
