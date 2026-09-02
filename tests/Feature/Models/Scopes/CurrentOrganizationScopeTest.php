<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Organization;
use App\Support\Tenancy\OrganizationContext;

test('querying a tenant-scoped model with no context set throws', function () {
    Client::query()->count();
})->throws(LogicException::class, 'No organization context has been established.');

test('querying a tenant-scoped model with context set only returns that organization rows', function () {
    $organizationA = Organization::factory()->create();
    $organizationB = Organization::factory()->create();

    $clientA = Client::factory()->for($organizationA)->create();
    Client::factory()->for($organizationB)->create();

    app(OrganizationContext::class)->set($organizationA->id);

    expect(Client::all())->toHaveCount(1)
        ->and(Client::query()->first()->is($clientA))->toBeTrue();
});
