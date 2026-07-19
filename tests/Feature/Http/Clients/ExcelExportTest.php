<?php

declare(strict_types=1);

use App\Exports\ClientsExport;
use App\Models\Client;
use App\Models\Organization;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;

test('guests are redirected to the login page', function () {
    $this->get(route('clients.export'))
        ->assertRedirect(route('login'));
});

test('authenticated user can download the clients export', function () {
    Excel::fake();

    $user = User::factory()->withOrganization()->create();
    Client::factory(2)->forOrganization($user)->create();

    $this->actingAs($user)
        ->get(route('clients.export'))
        ->assertOk();

    Excel::assertDownloaded('clients.xlsx');
});

test('the export only includes the current organization clients', function () {
    Excel::fake();

    $user = User::factory()->withOrganization()->create();

    /** @var Client $ownClient */
    $ownClient = Client::factory()->forOrganization($user)->create();

    $otherOrganization = Organization::factory()->create();
    Client::factory()->for($otherOrganization)->create();

    $this->actingAs($user)
        ->get(route('clients.export'))
        ->assertOk();

    Excel::assertDownloaded('clients.xlsx', function (ClientsExport $export) use ($ownClient) {
        return $export->query()->pluck('id')->all() === [$ownClient->id];
    });
});

test('a filter query param narrows the exported rows to matching clients', function () {
    Excel::fake();

    $user = User::factory()->withOrganization()->create();

    /** @var Client $match */
    $match = Client::factory()->forOrganization($user)->create(['first_name' => 'Aline']);
    Client::factory()->forOrganization($user)->create(['first_name' => 'Karim']);

    $this->actingAs($user)
        ->get(route('clients.export', ['search' => 'Aline']))
        ->assertOk();

    Excel::assertDownloaded('clients.xlsx', function (ClientsExport $export) use ($match) {
        return $export->query()->pluck('id')->all() === [$match->id];
    });
});

test('a sort query param reorders the exported rows', function () {
    Excel::fake();

    $user = User::factory()->withOrganization()->create();

    /** @var Client $bravo */
    $bravo = Client::factory()->forOrganization($user)->create(['first_name' => 'Bravo']);
    /** @var Client $alpha */
    $alpha = Client::factory()->forOrganization($user)->create(['first_name' => 'Alpha']);

    $this->actingAs($user)
        ->get(route('clients.export', ['sort' => 'name', 'direction' => 'asc']))
        ->assertOk();

    Excel::assertDownloaded('clients.xlsx', function (ClientsExport $export) use ($alpha, $bravo) {
        return $export->query()->pluck('id')->all() === [$alpha->id, $bravo->id];
    });
});

test('an invalid gender is rejected', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('clients.export', ['gender' => 'other']))
        ->assertInvalid(['gender']);
});
