<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Support\Tenancy\OrganizationContext;
use App\Support\Tenancy\OrganizationTimezone;

test('an organization without a timezone resolves to UTC and the UTC date', function () {
    $this->travelTo('2026-01-15 22:30:00');
    $organization = Organization::factory()->create();

    $resolver = app(OrganizationTimezone::class);

    expect($resolver->for($organization))->toBe('UTC')
        ->and($resolver->todayFor($organization)->toDateString())->toBe('2026-01-15');
});

test('an organization timezone overrides UTC for the local date', function () {
    $this->travelTo('2026-01-15 18:00:00');
    $organization = Organization::factory()->create(['timezone' => 'Asia/Tokyo']);

    $resolver = app(OrganizationTimezone::class);

    expect($resolver->for($organization))->toBe('Asia/Tokyo')
        ->and($resolver->todayFor($organization)->toDateString())->toBe('2026-01-16');
});

test('today follows the organization-local midnight, not the UTC one', function (string $instant, string $timezone, string $localDate) {
    $this->travelTo($instant);
    $organization = Organization::factory()->create(['timezone' => $timezone]);

    expect(app(OrganizationTimezone::class)->todayFor($organization)->toDateString())->toBe($localDate);
})->with([
    'east of UTC, just after local midnight' => ['2026-01-15 22:30:00', 'Asia/Beirut', '2026-01-16'],
    'west of UTC, just before local midnight' => ['2026-01-16 03:00:00', 'America/New_York', '2026-01-15'],
]);

test('the current-organization convenience resolves the organization in context', function () {
    $this->travelTo('2026-01-15 22:30:00');
    Organization::factory()->create();
    $organization = Organization::factory()->create(['timezone' => 'Asia/Beirut']);
    app(OrganizationContext::class)->set($organization->id);

    $resolver = app(OrganizationTimezone::class);

    expect($resolver->current())->toBe('Asia/Beirut')
        ->and($resolver->today()->toDateString())->toBe('2026-01-16');
});

test('resolving the local date leaves the application timezone in UTC', function () {
    $organization = Organization::factory()->create(['timezone' => 'Asia/Beirut']);

    app(OrganizationTimezone::class)->todayFor($organization);

    expect(config('app.timezone'))->toBe('UTC')
        ->and(now()->timezoneName)->toBe('UTC');
});
