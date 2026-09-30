<?php

declare(strict_types=1);

use App\Actions\Policies\CreatePolicyMedicalAction;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Policy;
use App\Models\PolicyInsured;
use App\Models\User;
use Illuminate\Database\QueryException;
use Tests\Support\PolicyPayload;

test('a single medical policy stores an insured profile on policy_medical_details', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    /** @noinspection PhpUnhandledExceptionInspection */
    $policy = app(CreatePolicyMedicalAction::class)->handle($user, PolicyPayload::medicalSingle($client, $carrier, [
        'medical' => ['coverage_scope' => 'in', 'insured_full_name' => 'Amelia Hartwell'],
    ]));

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
    $policy = app(CreatePolicyMedicalAction::class)->handle($user, PolicyPayload::medicalGroup($client, $carrier, [
        'insureds' => [
            PolicyPayload::insured(['full_name' => 'Lina Hartwell']),
            PolicyPayload::insured(['full_name' => 'Noah Hartwell']),
        ],
    ]));

    expect($policy->insureds)->toHaveCount(2)
        ->and($policy->medicalDetails->insured_full_name)->toBeNull();
});

test('an invalid dependent leaves no partial policy, detail, or insureds rows', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $attributes = PolicyPayload::medicalGroup($client, $carrier, [
        'insureds' => [
            PolicyPayload::insured(['full_name' => 'Lina Hartwell']),
            PolicyPayload::insured(['full_name' => 'Noah Hartwell']),
            PolicyPayload::insured(['full_name' => 'Iris Hartwell', 'date_of_birth' => null]),
        ],
    ]);

    $attempt = function () use ($user, $attributes): Policy {
        /** @noinspection PhpUnhandledExceptionInspection */
        return app(CreatePolicyMedicalAction::class)->handle($user, $attributes);
    };

    expect($attempt)->toThrow(QueryException::class)
        ->and(Policy::query()->count())->toBe(0)
        ->and(PolicyInsured::query()->count())->toBe(0);
});
