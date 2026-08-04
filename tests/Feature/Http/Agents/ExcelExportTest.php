<?php

declare(strict_types=1);

use App\Exports\AgentsExport;
use App\Models\Agent;
use App\Models\Organization;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;

test('guests are redirected to the login page', function () {
    $this->get(route('agents.export'))
        ->assertRedirect(route('login'));
});

test('authenticated user can download the agents export', function () {
    Excel::fake();

    $user = User::factory()->withOrganization()->create();
    Agent::factory(2)->forOrganization($user)->create();

    $this->actingAs($user)
        ->get(route('agents.export'))
        ->assertOk();

    Excel::assertDownloaded('agents.xlsx');
});

test('the export only includes the current organization agents', function () {
    Excel::fake();

    $user = User::factory()->withOrganization()->create();

    /** @var Agent $ownAgent */
    $ownAgent = Agent::factory()->forOrganization($user)->create();

    $otherOrganization = Organization::factory()->create();
    Agent::factory()->for($otherOrganization)->create();

    $this->actingAs($user)
        ->get(route('agents.export'))
        ->assertOk();

    Excel::assertDownloaded('agents.xlsx', function (AgentsExport $export) use ($ownAgent) {
        return $export->query()->pluck('id')->all() === [$ownAgent->id];
    });
});

test('a filter query param narrows the exported rows to matching agents', function () {
    Excel::fake();

    $user = User::factory()->withOrganization()->create();

    /** @var Agent $match */
    $match = Agent::factory()->forOrganization($user)->create(['first_name' => 'Mira', 'last_name' => 'Olsen']);
    Agent::factory()->forOrganization($user)->create(['first_name' => 'Nadia', 'last_name' => 'Fares']);

    $this->actingAs($user)
        ->get(route('agents.export', ['search' => 'Mira']))
        ->assertOk();

    Excel::assertDownloaded('agents.xlsx', function (AgentsExport $export) use ($match) {
        return $export->query()->pluck('id')->all() === [$match->id];
    });
});

test('a sort query param reorders the exported rows', function () {
    Excel::fake();

    $user = User::factory()->withOrganization()->create();

    /** @var Agent $bravo */
    $bravo = Agent::factory()->forOrganization($user)->create(['last_name' => 'Bravo']);
    /** @var Agent $alpha */
    $alpha = Agent::factory()->forOrganization($user)->create(['last_name' => 'Alpha']);

    $this->actingAs($user)
        ->get(route('agents.export', ['sort' => 'name', 'direction' => 'asc']))
        ->assertOk();

    Excel::assertDownloaded('agents.xlsx', function (AgentsExport $export) use ($alpha, $bravo) {
        return $export->query()->pluck('id')->all() === [$alpha->id, $bravo->id];
    });
});

test('an invalid sort column is rejected', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('agents.export', ['sort' => 'phone']))
        ->assertInvalid(['sort']);
});
