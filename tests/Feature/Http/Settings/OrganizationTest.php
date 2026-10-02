<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Organization;
use App\Models\User;

test('owner can view the organization settings page', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    $this->actingAs($owner)
        ->get(route('organization.edit'))
        ->assertOk();
});

test('admin cannot view the organization settings page', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->forOrganization($organization, OrganizationRole::Admin)->create();

    $this->actingAs($admin)
        ->get(route('organization.edit'))
        ->assertForbidden();
});

test('member cannot view the organization settings page', function () {
    $organization = Organization::factory()->create();
    $member = User::factory()->forOrganization($organization)->create();

    $this->actingAs($member)
        ->get(route('organization.edit'))
        ->assertForbidden();
});

test('owner can enable the organization two factor requirement', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $this->actingAs($owner)
        ->patch(route('organization.update'), ['two_factor_required' => true])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect($organization->fresh()->two_factor_required)->toBeTrue();
});

test('accepts the string "1" a browser form submission sends for the checked state', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $this->actingAs($owner)
        ->patch(route('organization.update'), ['two_factor_required' => '1'])
        ->assertSessionHasNoErrors();

    expect($organization->fresh()->two_factor_required)->toBeTrue();
});

test('accepts the string "0" a browser form submission sends for the unchecked state', function () {
    $organization = Organization::factory()->create(['two_factor_required' => true]);
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->withTwoFactor()->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $this->actingAs($owner)
        ->patch(route('organization.update'), ['two_factor_required' => '0'])
        ->assertSessionHasNoErrors();

    expect($organization->fresh()->two_factor_required)->toBeFalse();
});

test('admin cannot update the organization two factor requirement', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->forOrganization($organization, OrganizationRole::Admin)->create();

    $this->actingAs($admin)
        ->patch(route('organization.update'), ['two_factor_required' => true])
        ->assertForbidden();

    expect($organization->fresh()->two_factor_required)->toBeFalse();
});

test('updating the organization requires a boolean value', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    $this->actingAs($owner)
        ->patch(route('organization.update'), ['two_factor_required' => 'not-a-boolean'])
        ->assertSessionHasErrors('two_factor_required');
});

test('the organization settings page shows the current name and defaults', function () {
    $organization = Organization::factory()->withLebanonAndUsdDefaults()->create(['name' => 'Acme Insurance']);
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    $this->actingAs($owner)
        ->get(route('organization.edit'))
        ->assertInertia(fn ($page) => $page
            ->where('name', 'Acme Insurance')
            ->where('defaultCountryId', $organization->default_country_id)
            ->where('defaultCurrencyId', $organization->default_currency_id)
            ->where('currencies.0.id', $organization->default_currency_id)
            ->where('currencies.0.code', 'USD')
        );
});

test('owner can rename the organization and set its default country and currency', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $country = Country::factory()->create();
    $currency = Currency::factory()->create();

    $this->actingAs($owner)
        ->patch(route('organization.details.update'), [
            'name' => 'Acme Insurance',
            'default_country_id' => $country->id,
            'default_currency_id' => $currency->id,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $organization->refresh();

    expect($organization->name)->toBe('Acme Insurance')
        ->and($organization->default_country_id)->toBe($country->id)
        ->and($organization->default_currency_id)->toBe($currency->id);
});

test('owner can clear the default country and currency', function () {
    $organization = Organization::factory()->withLebanonAndUsdDefaults()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    $this->actingAs($owner)
        ->patch(route('organization.details.update'), [
            'name' => $organization->name,
            'default_country_id' => '',
            'default_currency_id' => '',
        ])
        ->assertSessionHasNoErrors();

    $organization->refresh();

    expect($organization->default_country_id)->toBeNull()
        ->and($organization->default_currency_id)->toBeNull();
});

test('non-owners cannot update the organization details', function (OrganizationRole $role) {
    $organization = Organization::factory()->create(['name' => 'Acme Insurance']);
    $user = User::factory()->forOrganization($organization, $role)->create();

    $this->actingAs($user)
        ->patch(route('organization.details.update'), [
            'name' => 'Renamed',
            'default_country_id' => null,
            'default_currency_id' => null,
        ])
        ->assertForbidden();

    expect($organization->fresh()->name)->toBe('Acme Insurance');
})->with([OrganizationRole::Admin, OrganizationRole::Member]);

test('updating the organization details rejects invalid input', function (array $input, string $field) {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    $this->actingAs($owner)
        ->patch(route('organization.details.update'), [
            'name' => 'Acme Insurance',
            'default_country_id' => null,
            'default_currency_id' => null,
            ...$input,
        ])
        ->assertSessionHasErrors($field);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'name too long' => [['name' => str_repeat('a', 256)], 'name'],
    'non-existent country' => [['default_country_id' => 999999], 'default_country_id'],
    'non-existent currency' => [['default_currency_id' => 999999], 'default_currency_id'],
]);
