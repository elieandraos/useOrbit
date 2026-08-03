<?php

declare(strict_types=1);

use App\Exports\CarriersExport;
use App\Models\Carrier;
use App\Models\Organization;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;

test('guests are redirected to the login page', function () {
    $this->get(route('carriers.export'))
        ->assertRedirect(route('login'));
});

test('authenticated user can download the carriers export', function () {
    Excel::fake();

    $user = User::factory()->withOrganization()->create();
    Carrier::factory(2)->forOrganization($user)->create();

    $this->actingAs($user)
        ->get(route('carriers.export'))
        ->assertOk();

    Excel::assertDownloaded('carriers.xlsx');
});

test('the export only includes the current organization carriers', function () {
    Excel::fake();

    $user = User::factory()->withOrganization()->create();

    /** @var Carrier $ownCarrier */
    $ownCarrier = Carrier::factory()->forOrganization($user)->create();

    $otherOrganization = Organization::factory()->create();
    Carrier::factory()->for($otherOrganization)->create();

    $this->actingAs($user)
        ->get(route('carriers.export'))
        ->assertOk();

    Excel::assertDownloaded('carriers.xlsx', function (CarriersExport $export) use ($ownCarrier) {
        return $export->query()->pluck('id')->all() === [$ownCarrier->id];
    });
});

test('a filter query param narrows the exported rows to matching carriers', function () {
    Excel::fake();

    $user = User::factory()->withOrganization()->create();

    /** @var Carrier $match */
    $match = Carrier::factory()->forOrganization($user)->create(['name' => 'Alpha Assurance']);
    Carrier::factory()->forOrganization($user)->create(['name' => 'Bravo Insurance']);

    $this->actingAs($user)
        ->get(route('carriers.export', ['search' => 'Alpha']))
        ->assertOk();

    Excel::assertDownloaded('carriers.xlsx', function (CarriersExport $export) use ($match) {
        return $export->query()->pluck('id')->all() === [$match->id];
    });
});

test('a sort query param reorders the exported rows', function () {
    Excel::fake();

    $user = User::factory()->withOrganization()->create();

    /** @var Carrier $bravo */
    $bravo = Carrier::factory()->forOrganization($user)->create(['name' => 'Bravo Insurance']);
    /** @var Carrier $alpha */
    $alpha = Carrier::factory()->forOrganization($user)->create(['name' => 'Alpha Assurance']);

    $this->actingAs($user)
        ->get(route('carriers.export', ['sort' => 'name', 'direction' => 'asc']))
        ->assertOk();

    Excel::assertDownloaded('carriers.xlsx', function (CarriersExport $export) use ($alpha, $bravo) {
        return $export->query()->pluck('id')->all() === [$alpha->id, $bravo->id];
    });
});

test('an invalid sort column is rejected', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('carriers.export', ['sort' => 'phone']))
        ->assertInvalid(['sort']);
});
