<?php

declare(strict_types=1);

use App\Models\Agent;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Currency;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Support\Arr;
use Tests\Support\PolicyPayload;

test('guests are redirected to the login page', function () {
    $this->post(route('policies.medical.store'))
        ->assertRedirect(route('login'));
});

test('store returns validation errors when required fields are missing', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->post(route('policies.medical.store'))
        ->assertSessionHasErrors(['policy_number', 'class', 'subclass', 'type', 'client_id', 'carrier_id', 'effective_date', 'expiry_date', 'premium_amount', 'source']);
});

test('store redirects to policies.medical.show with a toast on success', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->post(route('policies.medical.store'), PolicyPayload::medicalSingle($client, $carrier))
        ->assertRedirect(route('policies.medical.show', Policy::query()->first()))
        ->assertHasInertiaFlash('success', 'Policy created.');

    expect(Policy::query()->count())->toBe(1);
});

test('store wires the submitted client and carrier onto the created policy', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->post(route('policies.medical.store'), PolicyPayload::medicalSingle($client, $carrier))
        ->assertRedirect(route('policies.medical.show', Policy::query()->first()));

    $this->assertDatabaseHas('policies', [
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
    ]);
});

test('a group policy requires an insureds array', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = Arr::except(PolicyPayload::medicalGroup($client, $carrier), ['insureds']);

    $this->actingAs($user)
        ->post(route('policies.medical.store'), $payload)
        ->assertSessionHasErrors(['insureds']);
});

test('a group policy rejects an empty insureds list', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::medicalGroup($client, $carrier, ['insureds' => []]);

    $this->actingAs($user)
        ->post(route('policies.medical.store'), $payload)
        ->assertSessionHasErrors(['insureds']);

    expect(Policy::query()->count())->toBe(0);
});

test('a single policy prohibits an insureds array', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::medicalSingle($client, $carrier, [
        'insureds' => [['full_name' => 'Extra', 'relationship' => 'Child', 'date_of_birth' => '2020-01-01']],
    ]);

    $this->actingAs($user)
        ->post(route('policies.medical.store'), $payload)
        ->assertSessionHasErrors(['insureds']);
});

test('a policy number already used in the organization is rejected', function (bool $isSoftDeleted) {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $existing = Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'policy_number' => 'POL-1000']);

    if ($isSoftDeleted) {
        $existing->delete();
    }

    $this->actingAs($user)
        ->post(route('policies.medical.store'), PolicyPayload::medicalSingle($client, $carrier, ['policy_number' => 'POL-1000']))
        ->assertSessionHasErrors(['policy_number']);

    expect(Policy::withTrashed()->where('policy_number', 'POL-1000')->count())->toBe(1);
})->with(['active' => false, 'soft-deleted' => true]);

test('a policy number used in another organization is accepted', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    Policy::factory()->for(Organization::factory())->create(['policy_number' => 'POL-1000']);

    $this->actingAs($user)
        ->post(route('policies.medical.store'), PolicyPayload::medicalSingle($client, $carrier, ['policy_number' => 'POL-1000']))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('policies', ['organization_id' => $user->organization_id, 'policy_number' => 'POL-1000']);
});

test('a client belonging to a different organization is rejected', function () {
    $user = User::factory()->withOrganization()->create();
    $otherClient = Client::factory()->create();
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::medicalSingle($otherClient, $carrier);

    $this->actingAs($user)
        ->post(route('policies.medical.store'), $payload)
        ->assertSessionHasErrors(['client_id']);
});

test('a carrier belonging to a different organization is rejected', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $otherCarrier = Carrier::factory()->create();

    $payload = PolicyPayload::medicalSingle($client, $otherCarrier);

    $this->actingAs($user)
        ->post(route('policies.medical.store'), $payload)
        ->assertSessionHasErrors(['carrier_id']);
});

test('an archived client, carrier and agent are rejected', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->archived()->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->archived()->create(['created_by' => $user->id]);
    $agent = Agent::factory()->forOrganization($user)->archived()->create();

    $this->actingAs($user)
        ->post(route('policies.medical.store'), PolicyPayload::medicalSingle($client, $carrier, ['agent_id' => $agent->id]))
        ->assertSessionHasErrors(['client_id', 'carrier_id', 'agent_id']);

    expect(Policy::query()->count())->toBe(0);
});

test('a single policy is rejected when the insured profile fields are missing', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::medicalSingle($client, $carrier);
    Arr::forget($payload, ['medical.insured_full_name', 'medical.insured_date_of_birth', 'medical.insured_gender', 'medical.insured_smoker']);

    $this->actingAs($user)
        ->post(route('policies.medical.store'), $payload)
        ->assertSessionHasErrors(['medical.insured_full_name', 'medical.insured_date_of_birth', 'medical.insured_gender', 'medical.insured_smoker']);
});

test('a missing plan tier is reported by its form label', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::medicalSingle($client, $carrier);
    Arr::forget($payload, 'medical.class_tier');

    $this->actingAs($user)
        ->post(route('policies.medical.store'), $payload)
        ->assertSessionHasErrors(['medical.class_tier' => 'The plan tier field is required.']);
});

test('a co_insurance_share is required when co_insurance is true', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::medicalSingle($client, $carrier, ['medical' => ['co_insurance' => true]]);

    $this->actingAs($user)
        ->post(route('policies.medical.store'), $payload)
        ->assertSessionHasErrors(['medical.co_insurance_share']);
});

test('a co_insurance_share of 15 is accepted when co_insurance is true', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::medicalSingle($client, $carrier, [
        'medical' => ['co_insurance' => true, 'co_insurance_share' => 15],
    ]);

    /** @noinspection PhpUnhandledExceptionInspection */
    $this->actingAs($user)
        ->post(route('policies.medical.store'), $payload)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('policies.medical.show', Policy::query()->first()));
});

test('a two-decimal co_insurance_share is stored as submitted', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::medicalSingle($client, $carrier, [
        'medical' => ['co_insurance' => true, 'co_insurance_share' => '55.75'],
    ]);

    /** @noinspection PhpUnhandledExceptionInspection */
    $this->actingAs($user)
        ->post(route('policies.medical.store'), $payload)
        ->assertSessionHasNoErrors();

    expect(Policy::query()->sole()->medicalDetails->co_insurance_share)->toBe('55.75');
});

test('a group policy prohibits the single insured profile fields', function () {
    $user = User::factory()->withOrganization()->create();
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
        ->post(route('policies.medical.store'), $payload)
        ->assertSessionHasErrors(['medical.insured_full_name', 'medical.insured_date_of_birth', 'medical.insured_gender', 'medical.insured_smoker']);
});

test('a co_insurance_share is prohibited when co_insurance is false', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::medicalSingle($client, $carrier, [
        'medical' => ['co_insurance' => false, 'co_insurance_share' => 15],
    ]);

    $this->actingAs($user)
        ->post(route('policies.medical.store'), $payload)
        ->assertSessionHasErrors(['medical.co_insurance_share']);
});

test('every canonical medical subclass is accepted', function (string $subclass) {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::medicalSingle($client, $carrier, ['subclass' => $subclass]);

    $this->actingAs($user)
        ->post(route('policies.medical.store'), $payload)
        ->assertSessionHasNoErrors();
})->with(['Hospitalization', 'Outpatient', 'Dental', 'Vision', 'Major medical']);

test('a subclass outside the medical list is rejected', function (string $subclass) {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::medicalSingle($client, $carrier, ['subclass' => $subclass]);

    $this->actingAs($user)
        ->post(route('policies.medical.store'), $payload)
        ->assertSessionHasErrors(['subclass']);
})->with(['In-Out', 'Term']);

test('a discount up to the premium is accepted', function (?string $discount) {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->post(route('policies.medical.store'), PolicyPayload::medicalSingle($client, $carrier, ['premium_amount' => '1200.00', 'discount_amount' => $discount]))
        ->assertSessionHasNoErrors();
})->with(['equal to the premium' => '1200.00', 'null' => null]);

test('a discount greater than the premium is rejected', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->post(route('policies.medical.store'), PolicyPayload::medicalSingle($client, $carrier, ['premium_amount' => '1200.00', 'discount_amount' => '1200.01']))
        ->assertSessionHasErrors(['discount_amount']);
});

test('a single insured born today is accepted', function () {
    $this->freezeTime();
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->post(route('policies.medical.store'), PolicyPayload::medicalSingle($client, $carrier, ['medical' => ['insured_date_of_birth' => today()->toDateString()]]))
        ->assertSessionHasNoErrors();
});

test('a single insured born in the future is rejected', function () {
    $this->freezeTime();
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->post(route('policies.medical.store'), PolicyPayload::medicalSingle($client, $carrier, ['medical' => ['insured_date_of_birth' => today()->addDay()->toDateString()]]))
        ->assertSessionHasErrors(['medical.insured_date_of_birth']);
});

test('a covered member born today is accepted', function () {
    $this->freezeTime();
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->post(route('policies.medical.store'), PolicyPayload::medicalGroup($client, $carrier, ['insureds' => [PolicyPayload::insured(['date_of_birth' => today()->toDateString()])]]))
        ->assertSessionHasNoErrors();
});

test('a covered member born in the future is rejected', function () {
    $this->freezeTime();
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->post(route('policies.medical.store'), PolicyPayload::medicalGroup($client, $carrier, ['insureds' => [PolicyPayload::insured(['date_of_birth' => today()->addDay()->toDateString()])]]))
        ->assertSessionHasErrors(['insureds.0.date_of_birth']);
});

test('store saves the selected currency', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $currency = Currency::factory()->create();

    $this->actingAs($user)
        ->post(route('policies.medical.store'), PolicyPayload::medicalSingle($client, $carrier, ['currency_id' => $currency->id]))
        ->assertSessionHasNoErrors();

    expect(Policy::query()->sole()->currency_id)->toBe($currency->id);
});

test('store rejects a missing or non-existent currency', function (?int $currencyId) {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->post(route('policies.medical.store'), PolicyPayload::medicalSingle($client, $carrier, ['currency_id' => $currencyId]))
        ->assertSessionHasErrors(['currency_id']);

    expect(Policy::query()->exists())->toBeFalse();
})->with(['missing' => [null], 'non-existent' => [999999]]);

test('store accepts the largest premium and discount a policy holds', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->post(route('policies.medical.store'), PolicyPayload::medicalSingle($client, $carrier, [
            'premium_amount' => '9999999999999.99',
            'discount_amount' => '9999999999999.99',
        ]))
        ->assertSessionHasNoErrors();

    expect(Policy::query()->count())->toBe(1);
});

test('store rejects a premium or discount above the largest amount or with more than two decimals', function (string $field, string $amount) {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->post(route('policies.medical.store'), PolicyPayload::medicalSingle($client, $carrier, [
            'premium_amount' => '9999999999999.99',
            $field => $amount,
        ]))
        ->assertSessionHasErrors([$field]);
})->with([
    'premium above the maximum' => ['premium_amount', '10000000000000.00'],
    'premium with three decimals' => ['premium_amount', '1200.001'],
    'discount above the maximum' => ['discount_amount', '10000000000000.00'],
    'discount with three decimals' => ['discount_amount', '10.001'],
]);
