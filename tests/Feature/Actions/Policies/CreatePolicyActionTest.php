<?php

declare(strict_types=1);

use App\Actions\Policies\CreatePolicyAction;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\PolicyInsured;
use App\Models\User;
use App\Support\Tenancy\OrganizationContext;
use Illuminate\Database\QueryException;

function singleMedicalAttributes(Client $client, Carrier $carrier): array
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
        'premium_amount' => '1200.00',
        'discount_amount' => null,
        'status' => 'active',
        'source' => 'client',
        'medical' => [
            'coverage_scope' => 'in',
            'class_tier' => 'class_a',
            'co_insurance' => false,
            'co_insurance_share' => null,
            'guaranteed_renewable' => true,
            'insured_full_name' => 'Amelia Hartwell',
            'insured_date_of_birth' => '1986-03-22',
            'insured_gender' => 'female',
            'insured_smoker' => false,
            'insured_medical_history' => null,
        ],
    ];
}

function groupMedicalAttributes(Client $client, Carrier $carrier): array
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
        'insureds' => [
            ['full_name' => 'Lina Hartwell', 'relationship' => 'Spouse', 'date_of_birth' => '1988-08-08', 'gender' => 'female', 'medical_notes' => null],
            ['full_name' => 'Noah Hartwell', 'relationship' => 'Child', 'date_of_birth' => '2015-04-14', 'gender' => 'male', 'medical_notes' => 'Mild asthma'],
        ],
    ];
}

test('a blank policy_number is auto-generated', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    /** @noinspection PhpUnhandledExceptionInspection */
    $policy = app(CreatePolicyAction::class)->handle($user, singleMedicalAttributes($client, $carrier));

    expect($policy->policy_number)->toBe('POL-0001');
});

test('auto-generated policy numbers increment per organization', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    /** @noinspection PhpUnhandledExceptionInspection */
    $first = app(CreatePolicyAction::class)->handle($user, singleMedicalAttributes($client, $carrier));
    /** @noinspection PhpUnhandledExceptionInspection */
    $second = app(CreatePolicyAction::class)->handle($user, singleMedicalAttributes($client, $carrier));

    expect($first->policy_number)->toBe('POL-0001')
        ->and($second->policy_number)->toBe('POL-0002');
});

test('a submitted policy_number is respected', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $attributes = singleMedicalAttributes($client, $carrier);
    $attributes['policy_number'] = 'CUSTOM-001';

    /** @noinspection PhpUnhandledExceptionInspection */
    $policy = app(CreatePolicyAction::class)->handle($user, $attributes);

    expect($policy->policy_number)->toBe('CUSTOM-001');
});

test('sets created_by to the user id', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    /** @noinspection PhpUnhandledExceptionInspection */
    $policy = app(CreatePolicyAction::class)->handle($user, singleMedicalAttributes($client, $carrier));

    expect($policy->created_by)->toBe($user->id);
});

test('creates the policy scoped to the organization context', function () {
    $user = User::factory()->withOrganization()->create();
    $otherOrganization = Organization::factory()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    app(OrganizationContext::class)->set($otherOrganization->id);

    /** @noinspection PhpUnhandledExceptionInspection */
    $policy = app(CreatePolicyAction::class)->handle($user, singleMedicalAttributes($client, $carrier));

    expect($policy->organization_id)->toBe($otherOrganization->id);
});

test('a single medical policy stores an insured profile on policy_medical_details', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    /** @noinspection PhpUnhandledExceptionInspection */
    $policy = app(CreatePolicyAction::class)->handle($user, singleMedicalAttributes($client, $carrier));

    expect($policy->medicalDetails->insured_full_name)->toBe('Amelia Hartwell')
        ->and($policy->medicalDetails->coverage_scope->value)->toBe('in')
        ->and($policy->insureds)->toHaveCount(0);
});

test('a group medical policy stores dependents in policy_insureds with sequential member codes', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    /** @noinspection PhpUnhandledExceptionInspection */
    $policy = app(CreatePolicyAction::class)->handle($user, groupMedicalAttributes($client, $carrier));

    expect($policy->insureds)->toHaveCount(2)
        ->and($policy->insureds[0]->member_code)->toBe('MBR-001')
        ->and($policy->insureds[1]->member_code)->toBe('MBR-002')
        ->and($policy->medicalDetails->insured_full_name)->toBeNull();
});

test('an invalid dependent leaves no partial policy, detail, or insureds rows', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $attributes = groupMedicalAttributes($client, $carrier);
    $attributes['insureds'][] = ['full_name' => 'Iris Hartwell', 'relationship' => 'Child', 'date_of_birth' => null, 'gender' => null, 'medical_notes' => null];

    $attempt = function () use ($user, $attributes): Policy {
        /** @noinspection PhpUnhandledExceptionInspection */
        return app(CreatePolicyAction::class)->handle($user, $attributes);
    };

    expect($attempt)->toThrow(QueryException::class)
        ->and(Policy::query()->count())->toBe(0)
        ->and(PolicyInsured::query()->count())->toBe(0);
});
