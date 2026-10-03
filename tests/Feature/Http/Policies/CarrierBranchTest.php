<?php

declare(strict_types=1);

use App\Models\Carrier;
use App\Models\CarrierBranch;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\State;
use App\Models\User;
use Tests\Support\PolicyPayload;

/**
 * A valid store or update payload for the given policy class.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function carrierBranchPolicyPayload(string $class, Client $client, Carrier $carrier, array $overrides = []): array
{
    return match ($class) {
        'automotive' => PolicyPayload::automotive($client, $carrier, $overrides),
        'expat' => PolicyPayload::expat($client, $carrier, $overrides),
        'fire' => PolicyPayload::fire($client, $carrier, State::factory()->lebanon()->create(), $overrides),
        'life' => PolicyPayload::life($client, $carrier, $overrides),
        'medical' => PolicyPayload::medicalSingle($client, $carrier, $overrides),
        'travel' => PolicyPayload::travel($client, $carrier, $overrides),
    };
}

dataset('policy classes', ['automotive', 'expat', 'fire', 'life', 'medical', 'travel']);

test('store saves the submitted branch of the carrier', function (string $class) {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $branch = CarrierBranch::factory()->forCarrier($carrier)->create();

    $this->actingAs($user)
        ->post(route("policies.$class.store"), carrierBranchPolicyPayload($class, $client, $carrier, ['carrier_branch_id' => $branch->id]))
        ->assertSessionHasNoErrors();

    expect(Policy::query()->sole()->carrier_branch_id)->toBe($branch->id);
})->with('policy classes');

test('store saves no branch when none is submitted', function (string $class, array $branchInput) {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = carrierBranchPolicyPayload($class, $client, $carrier, $branchInput);

    $this->actingAs($user)
        ->post(route("policies.$class.store"), $payload)
        ->assertSessionHasNoErrors();

    expect(Policy::query()->sole()->carrier_branch_id)->toBeNull();
})->with('policy classes')->with(['left out' => [[]], 'empty' => [['carrier_branch_id' => null]]]);

test('store rejects a branch of another carrier in the organization or of another organization', function (string $class) {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $sameOrganizationBranch = CarrierBranch::factory()->forCarrier(Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]))->create();
    $otherOrganizationBranch = CarrierBranch::factory()->forCarrier(Carrier::factory()->for(Organization::factory())->create())->create();

    foreach ([$sameOrganizationBranch, $otherOrganizationBranch] as $branch) {
        $this->actingAs($user)
            ->post(route("policies.$class.store"), carrierBranchPolicyPayload($class, $client, $carrier, ['carrier_branch_id' => $branch->id]))
            ->assertSessionHasErrors(['carrier_branch_id' => 'The selected issuing branch is invalid.']);
    }

    expect(Policy::query()->count())->toBe(0);
})->with('policy classes');

test('update rejects the old carrier\'s branch after a carrier change, and saves no branch when none is submitted', function (string $class) {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->{$class}()->withCarrierBranch()->create([
        'created_by' => $user->id,
        'type' => 'single',
        'carrier_id' => Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id])->id,
    ]);
    $newCarrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route("policies.$class.update", $policy), carrierBranchPolicyPayload($class, $client, $newCarrier, ['carrier_branch_id' => $policy->carrier_branch_id]))
        ->assertSessionHasErrors(['carrier_branch_id']);

    $this->actingAs($user)
        ->patch(route("policies.$class.update", $policy), carrierBranchPolicyPayload($class, $client, $newCarrier))
        ->assertSessionHasNoErrors();

    expect($policy->fresh())
        ->carrier_id->toBe($newCarrier->id)
        ->carrier_branch_id->toBeNull();
})->with('policy classes');

test('update keeps the branch of the policy\'s own archived carrier', function (string $class) {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->archived()->create(['created_by' => $user->id]);
    $branch = CarrierBranch::factory()->forCarrier($carrier)->create();
    $policy = Policy::factory()->forOrganization($user)->{$class}()->create([
        'created_by' => $user->id,
        'type' => 'single',
        'carrier_id' => $carrier->id,
    ]);

    $this->actingAs($user)
        ->patch(route("policies.$class.update", $policy), carrierBranchPolicyPayload($class, $client, $carrier, ['carrier_branch_id' => $branch->id]))
        ->assertSessionHasNoErrors();

    expect($policy->fresh()->carrier_branch_id)->toBe($branch->id);
})->with('policy classes');

test('the show page exposes the branch\'s current label, and a null branch for a policy without one', function (string $class) {
    $user = User::factory()->withOrganization()->create();
    $parties = [
        'created_by' => $user->id,
        'client_id' => Client::factory()->forOrganization($user)->create(['created_by' => $user->id])->id,
        'carrier_id' => Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id])->id,
    ];
    $policyWithBranch = Policy::factory()->forOrganization($user)->{$class}()->withCarrierBranch()->create($parties);
    $policyWithoutBranch = Policy::factory()->forOrganization($user)->{$class}()->create($parties);
    $policyWithBranch->carrierBranch->update(['city' => 'Saida', 'street' => null]);

    $this->actingAs($user)
        ->get(route("policies.$class.show", $policyWithBranch))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('policy.carrier_branch', [
            'id' => $policyWithBranch->carrier_branch_id,
            'label' => 'Saida',
        ]));

    $this->actingAs($user)
        ->get(route("policies.$class.show", $policyWithoutBranch))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('policy.carrier_branch', null));
})->with('policy classes');
