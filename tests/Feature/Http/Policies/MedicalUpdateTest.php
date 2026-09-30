<?php

declare(strict_types=1);

use App\Models\Carrier;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\PolicyInsured;
use App\Models\User;
use Illuminate\Support\Arr;
use Tests\Support\PolicyPayload;

test('guests are redirected to the login page', function () {
    $policy = Policy::factory()->medical()->create(['type' => 'single']);

    $this->patch(route('policies.medical.update', $policy))
        ->assertRedirect(route('login'));
});

test('a user gets 404 updating a policy from another organization', function () {
    $user = User::factory()->withOrganization()->create();
    $otherOrganization = Organization::factory()->create();
    $policy = Policy::factory()->medical()->create(['organization_id' => $otherOrganization->id, 'type' => 'single']);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.medical.update', $policy), PolicyPayload::medicalSingle($client, $carrier))
        ->assertNotFound();
});

test('a user gets 404 updating a non-medical policy', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->automotive()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.medical.update', $policy), PolicyPayload::medicalSingle($client, $carrier))
        ->assertNotFound();
});

test('a user gets 404 rather than validation errors updating a non-medical policy with an invalid payload', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->automotive()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.medical.update', $policy))
        ->assertNotFound();
});

test('update returns validation errors when required fields are missing', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id, 'type' => 'single']);

    $this->actingAs($user)
        ->patch(route('policies.medical.update', $policy))
        ->assertSessionHasErrors(['policy_number', 'class', 'subclass', 'type', 'client_id', 'carrier_id', 'effective_date', 'expiry_date', 'premium_amount', 'source']);
});

test('update redirects to policies.medical.show with a toast on success', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id, 'type' => 'single']);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.medical.update', $policy), PolicyPayload::medicalSingle($client, $carrier))
        ->assertRedirect(route('policies.medical.show', $policy->fresh()))
        ->assertHasInertiaFlash('success', 'Policy updated.');
});

test('a policy number held by another policy in the organization is rejected', function (bool $isSoftDeleted) {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id, 'type' => 'single']);
    $other = Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'policy_number' => 'POL-1000']);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    if ($isSoftDeleted) {
        $other->delete();
    }

    $this->actingAs($user)
        ->patch(route('policies.medical.update', $policy), PolicyPayload::medicalSingle($client, $carrier, ['policy_number' => 'POL-1000']))
        ->assertSessionHasErrors(['policy_number']);
})->with(['active' => false, 'soft-deleted' => true]);

test('keeping the policy\'s own number on update is accepted', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id, 'type' => 'single', 'policy_number' => 'POL-1000']);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.medical.update', $policy), PolicyPayload::medicalSingle($client, $carrier, ['policy_number' => 'POL-1000']))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('policies.medical.show', $policy->fresh()));
});

test('update wires the submitted client and carrier onto the policy', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id, 'type' => 'single']);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.medical.update', $policy), PolicyPayload::medicalSingle($client, $carrier));

    $this->assertDatabaseHas('policies', [
        'id' => $policy->id,
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
    ]);
});

test('an insureds.*.id belonging to another policy is rejected', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id, 'type' => 'group']);
    $otherPolicy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id, 'type' => 'group']);
    $otherMember = PolicyInsured::factory()->for($otherPolicy)->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::medicalGroup($client, $carrier, [
        'insureds' => [PolicyPayload::insured(['id' => $otherMember->id])],
    ]);

    $this->actingAs($user)
        ->patch(route('policies.medical.update', $policy), $payload)
        ->assertSessionHasErrors(['insureds.0.id']);
});

test('a group policy requires an insureds array', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id, 'type' => 'group']);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = Arr::except(PolicyPayload::medicalGroup($client, $carrier), ['insureds']);

    $this->actingAs($user)
        ->patch(route('policies.medical.update', $policy), $payload)
        ->assertSessionHasErrors(['insureds']);
});

test('a group policy rejects an empty insureds list', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id, 'type' => 'group']);
    $member = PolicyInsured::factory()->for($policy)->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.medical.update', $policy), PolicyPayload::medicalGroup($client, $carrier, ['insureds' => []]))
        ->assertSessionHasErrors(['insureds']);

    $this->assertModelExists($member);
});

test('a single policy prohibits an insureds array', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id, 'type' => 'single']);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::medicalSingle($client, $carrier, [
        'insureds' => [['full_name' => 'Extra', 'relationship' => 'Child', 'date_of_birth' => '2020-01-01']],
    ]);

    $this->actingAs($user)
        ->patch(route('policies.medical.update', $policy), $payload)
        ->assertSessionHasErrors(['insureds']);
});

test('a single policy is rejected when the insured profile fields are missing', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id, 'type' => 'single']);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::medicalSingle($client, $carrier);
    Arr::forget($payload, ['medical.insured_full_name', 'medical.insured_date_of_birth', 'medical.insured_gender', 'medical.insured_smoker']);

    $this->actingAs($user)
        ->patch(route('policies.medical.update', $policy), $payload)
        ->assertSessionHasErrors(['medical.insured_full_name', 'medical.insured_date_of_birth', 'medical.insured_gender', 'medical.insured_smoker']);
});

test('a group policy prohibits the single insured profile fields', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id, 'type' => 'group']);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::medicalGroup($client, $carrier, [
        'medical' => [
            'insured_full_name' => 'Amelia Hartwell',
            'insured_date_of_birth' => '1986-03-22',
            'insured_gender' => 'female',
            'insured_smoker' => false,
        ],
    ]);

    $this->actingAs($user)
        ->patch(route('policies.medical.update', $policy), $payload)
        ->assertSessionHasErrors(['medical.insured_full_name', 'medical.insured_date_of_birth', 'medical.insured_gender', 'medical.insured_smoker']);
});

test('a co_insurance_share is required when co_insurance is true', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id, 'type' => 'single']);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::medicalSingle($client, $carrier, ['medical' => ['co_insurance' => true]]);

    $this->actingAs($user)
        ->patch(route('policies.medical.update', $policy), $payload)
        ->assertSessionHasErrors(['medical.co_insurance_share']);
});

test('a co_insurance_share is prohibited when co_insurance is false', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id, 'type' => 'single']);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::medicalSingle($client, $carrier, [
        'medical' => ['co_insurance' => false, 'co_insurance_share' => 15],
    ]);

    $this->actingAs($user)
        ->patch(route('policies.medical.update', $policy), $payload)
        ->assertSessionHasErrors(['medical.co_insurance_share']);
});

test('every canonical medical subclass is accepted', function (string $subclass) {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id, 'type' => 'single']);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::medicalSingle($client, $carrier, ['subclass' => $subclass]);

    $this->actingAs($user)
        ->patch(route('policies.medical.update', $policy), $payload)
        ->assertSessionHasNoErrors();
})->with(['Hospitalization', 'Outpatient', 'Dental', 'Vision', 'Major medical']);

test('a subclass outside the medical list is rejected', function (string $subclass) {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id, 'type' => 'single']);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::medicalSingle($client, $carrier, ['subclass' => $subclass]);

    $this->actingAs($user)
        ->patch(route('policies.medical.update', $policy), $payload)
        ->assertSessionHasErrors(['subclass']);
})->with(['In-Out', 'Term']);
