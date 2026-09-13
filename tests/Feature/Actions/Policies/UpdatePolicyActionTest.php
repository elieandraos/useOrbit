<?php

declare(strict_types=1);

use App\Actions\Policies\UpdatePolicyAction;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Policy;
use App\Models\PolicyInsured;
use App\Models\User;
use Illuminate\Database\QueryException;

function singleMedicalUpdateAttributes(Client $client, Carrier $carrier): array
{
    return [
        'policy_number' => null,
        'class' => 'medical',
        'subclass' => 'In',
        'type' => 'single',
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
        'agent_id' => null,
        'effective_date' => '2026-01-01',
        'expiry_date' => '2027-01-01',
        'premium_amount' => '1500.00',
        'discount_amount' => null,
        'status' => 'active',
        'source' => 'client',
        'medical' => [
            'coverage_scope' => 'in',
            'class_tier' => 'class_a',
            'co_insurance' => false,
            'co_insurance_share' => null,
            'guaranteed_renewable' => true,
            'insured_full_name' => 'Updated Name',
            'insured_date_of_birth' => '1986-03-22',
            'insured_gender' => 'female',
            'insured_smoker' => false,
            'insured_medical_history' => null,
        ],
    ];
}

function groupMedicalUpdateAttributes(Client $client, Carrier $carrier, array $insureds = []): array
{
    return [
        'policy_number' => null,
        'class' => 'medical',
        'subclass' => 'In-Out',
        'type' => 'group',
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
        'agent_id' => null,
        'effective_date' => '2026-01-01',
        'expiry_date' => '2027-01-01',
        'premium_amount' => '28400.00',
        'discount_amount' => '1200.00',
        'status' => 'active',
        'source' => 'owner',
        'medical' => [
            'coverage_scope' => 'in_out',
            'class_tier' => 'class_b',
            'co_insurance' => true,
            'co_insurance_share' => '15.00',
            'guaranteed_renewable' => true,
            'insured_full_name' => null,
            'insured_date_of_birth' => null,
            'insured_gender' => null,
            'insured_smoker' => null,
            'insured_medical_history' => null,
        ],
        'insureds' => $insureds,
    ];
}

test('updates the policy fields in the database', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->medical()->create([
        'created_by' => $user->id,
        'type' => 'single',
        'premium_amount' => '1000.00',
    ]);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdatePolicyAction::class)->handle($user, $policy, singleMedicalUpdateAttributes($client, $carrier));

    $fresh = $policy->fresh();
    expect($fresh->premium_amount)->toBe('1500.00')
        ->and($fresh->client_id)->toBe($client->id)
        ->and($fresh->carrier_id)->toBe($carrier->id);
});

test('sets updated_by to the user id', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->medical()->create([
        'created_by' => $user->id,
        'type' => 'single',
    ]);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdatePolicyAction::class)->handle($user, $policy, singleMedicalUpdateAttributes($client, $carrier));

    expect($policy->fresh()->updated_by)->toBe($user->id);
});

test('regenerates the slug when policy_number changes', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->medical()->create([
        'created_by' => $user->id,
        'type' => 'single',
        'policy_number' => 'POL-0001',
        'slug' => 'pol-0001',
    ]);

    $attributes = singleMedicalUpdateAttributes($client, $carrier);
    $attributes['policy_number'] = 'POL-9999';

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdatePolicyAction::class)->handle($user, $policy, $attributes);

    expect($policy->fresh()->slug)->toBe('pol-9999');
});

test('keeps the existing slug when policy_number does not change', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->medical()->create([
        'created_by' => $user->id,
        'type' => 'single',
        'policy_number' => 'POL-0001',
        'slug' => 'pol-0001',
    ]);

    $attributes = singleMedicalUpdateAttributes($client, $carrier);
    $attributes['policy_number'] = 'POL-0001';

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdatePolicyAction::class)->handle($user, $policy, $attributes);

    expect($policy->fresh()->slug)->toBe('pol-0001');
});

test('updates the policy_medical_details row in place', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->medical()->create([
        'created_by' => $user->id,
        'type' => 'single',
    ]);
    $detailsId = $policy->medicalDetails->id;

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdatePolicyAction::class)->handle($user, $policy, singleMedicalUpdateAttributes($client, $carrier));

    $fresh = $policy->fresh('medicalDetails');
    expect($fresh->medicalDetails->id)->toBe($detailsId)
        ->and($fresh->medicalDetails->insured_full_name)->toBe('Updated Name');
});

test('an existing member id updates that row in place instead of replacing it', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->medical()->create([
        'created_by' => $user->id,
        'type' => 'group',
    ]);
    $member = PolicyInsured::factory()->for($policy)->create([
        'member_code' => 'MBR-001',
        'full_name' => 'Original Name',
    ]);

    $attributes = groupMedicalUpdateAttributes($client, $carrier, [
        ['id' => $member->id, 'full_name' => 'Renamed Member', 'relationship' => 'Spouse', 'date_of_birth' => '1988-08-08', 'gender' => 'female', 'medical_notes' => null],
    ]);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdatePolicyAction::class)->handle($user, $policy, $attributes);

    expect($policy->insureds)->toHaveCount(1)
        ->and($policy->insureds[0]->id)->toBe($member->id)
        ->and($policy->insureds[0]->member_code)->toBe('MBR-001')
        ->and($policy->insureds[0]->full_name)->toBe('Renamed Member');
});

test('omitting a previously-existing member removes it', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->medical()->create([
        'created_by' => $user->id,
        'type' => 'group',
    ]);
    $kept = PolicyInsured::factory()->for($policy)->create(['member_code' => 'MBR-001']);
    $removed = PolicyInsured::factory()->for($policy)->create(['member_code' => 'MBR-002']);

    $attributes = groupMedicalUpdateAttributes($client, $carrier, [
        ['id' => $kept->id, 'full_name' => $kept->full_name, 'relationship' => $kept->relationship, 'date_of_birth' => $kept->date_of_birth->format('Y-m-d'), 'gender' => $kept->gender?->value, 'medical_notes' => null],
    ]);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdatePolicyAction::class)->handle($user, $policy, $attributes);

    expect($policy->insureds)->toHaveCount(1)
        ->and($policy->insureds->pluck('id'))->not->toContain($removed->id);
});

test('a newly added member receives the next sequential member_code without reusing a removed one', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->medical()->create([
        'created_by' => $user->id,
        'type' => 'group',
    ]);
    $kept = PolicyInsured::factory()->for($policy)->create(['member_code' => 'MBR-001']);
    PolicyInsured::factory()->for($policy)->create(['member_code' => 'MBR-002']);

    $attributes = groupMedicalUpdateAttributes($client, $carrier, [
        ['id' => $kept->id, 'full_name' => $kept->full_name, 'relationship' => $kept->relationship, 'date_of_birth' => $kept->date_of_birth->format('Y-m-d'), 'gender' => $kept->gender?->value, 'medical_notes' => null],
        ['full_name' => 'New Member', 'relationship' => 'Child', 'date_of_birth' => '2018-01-01', 'gender' => 'male', 'medical_notes' => null],
    ]);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdatePolicyAction::class)->handle($user, $policy, $attributes);

    $memberCodes = $policy->insureds()->pluck('member_code')->all();
    expect($memberCodes)->toContain('MBR-003')
        ->and($memberCodes)->not->toContain('MBR-002');
});

test('switching type away from group removes all insureds', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->medical()->create([
        'created_by' => $user->id,
        'type' => 'group',
    ]);
    PolicyInsured::factory()->for($policy)->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdatePolicyAction::class)->handle($user, $policy, singleMedicalUpdateAttributes($client, $carrier));

    expect($policy->insureds()->count())->toBe(0);
});

test('an invalid member leaves the policy, its details, and its insureds unchanged', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->medical()->create([
        'created_by' => $user->id,
        'type' => 'group',
        'premium_amount' => '1000.00',
    ]);
    $kept = PolicyInsured::factory()->for($policy)->create(['member_code' => 'MBR-001']);

    $attributes = groupMedicalUpdateAttributes($client, $carrier, [
        ['id' => $kept->id, 'full_name' => $kept->full_name, 'relationship' => $kept->relationship, 'date_of_birth' => $kept->date_of_birth->format('Y-m-d'), 'gender' => $kept->gender?->value, 'medical_notes' => null],
        ['full_name' => 'Invalid Member', 'relationship' => 'Child', 'date_of_birth' => null, 'gender' => null, 'medical_notes' => null],
    ]);

    $attempt = function () use ($user, $policy, $attributes): Policy {
        /** @noinspection PhpUnhandledExceptionInspection */
        return app(UpdatePolicyAction::class)->handle($user, $policy, $attributes);
    };

    expect($attempt)->toThrow(QueryException::class)
        ->and($policy->fresh()->premium_amount)->toBe('1000.00')
        ->and(PolicyInsured::query()->whereKey($kept->id)->exists())->toBeTrue()
        ->and(PolicyInsured::query()->count())->toBe(1);
});
