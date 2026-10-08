<?php

declare(strict_types=1);

use App\Enums\PolicyClass;
use App\Enums\PolicyDisplayStatus;
use App\Exports\PoliciesExport;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;

test('guests are redirected to the login page', function () {
    $this->get(route('policies.export'))
        ->assertRedirect(route('login'));
});

test('authenticated user can download the policies export', function () {
    Excel::fake();

    $user = User::factory()->withOrganization()->create();
    Policy::factory(2)->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('policies.export'))
        ->assertOk();

    Excel::assertDownloaded('policies.xlsx');
});

test('the export only includes the current organization policies', function () {
    Excel::fake();

    $user = User::factory()->withOrganization()->create();

    /** @var Policy $ownPolicy */
    $ownPolicy = Policy::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $otherOrganization = Organization::factory()->create();
    Policy::factory()->create(['organization_id' => $otherOrganization->id]);

    $this->actingAs($user)
        ->get(route('policies.export'))
        ->assertOk();

    Excel::assertDownloaded('policies.xlsx', function (PoliciesExport $export) use ($ownPolicy) {
        return $export->query()->pluck('id')->all() === [$ownPolicy->id];
    });
});

test('a filter query param narrows the exported rows to matching policies', function () {
    Excel::fake();

    $user = User::factory()->withOrganization()->create();

    /** @var Policy $match */
    $match = Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'policy_number' => 'POL-1000']);
    Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'policy_number' => 'POL-2000']);

    $this->actingAs($user)
        ->get(route('policies.export', ['search' => '1000']))
        ->assertOk();

    Excel::assertDownloaded('policies.xlsx', function (PoliciesExport $export) use ($match) {
        return $export->query()->pluck('id')->all() === [$match->id];
    });
});

test('a class[] filter narrows the exported rows to any of the selected classes', function () {
    Excel::fake();

    $user = User::factory()->withOrganization()->create();

    /** @var Policy $fire */
    $fire = Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'class' => PolicyClass::Fire]);
    Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'class' => PolicyClass::Travel]);

    $this->actingAs($user)
        ->get(route('policies.export', ['class' => [PolicyClass::Fire->value]]))
        ->assertOk();

    Excel::assertDownloaded('policies.xlsx', function (PoliciesExport $export) use ($fire) {
        return $export->query()->pluck('id')->all() === [$fire->id];
    });
});

test('a sort query param does not reorder the exported rows', function () {
    Excel::fake();

    $user = User::factory()->withOrganization()->create();

    /** @var Policy $bravo */
    $bravo = Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'policy_number' => 'POL-B', 'effective_date' => '2024-06-01']);
    /** @var Policy $alpha */
    $alpha = Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'policy_number' => 'POL-A', 'effective_date' => '2024-06-01']);

    $this->actingAs($user)
        ->get(route('policies.export', ['sort' => 'policy_number', 'direction' => 'asc']))
        ->assertOk();

    Excel::assertDownloaded('policies.xlsx', function (PoliciesExport $export) use ($alpha, $bravo) {
        return $export->query()->pluck('id')->all() === [$bravo->id, $alpha->id];
    });
});

test('a status filter narrows the exported rows to the matching display status, as on the index', function () {
    Excel::fake();
    $this->travelTo('2026-03-10 12:00:00');

    $user = User::factory()->withOrganization()->create();
    $parties = [
        'created_by' => $user->id,
        'client_id' => Client::factory()->forOrganization($user)->create(['created_by' => $user->id])->id,
        'carrier_id' => Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id])->id,
    ];

    /** @var Policy $upcoming */
    $upcoming = Policy::factory()->forOrganization($user)->create([...$parties, 'effective_date' => '2026-03-11', 'expiry_date' => '2027-03-10']);
    Policy::factory()->forOrganization($user)->create([...$parties, 'effective_date' => '2026-03-10', 'expiry_date' => '2027-03-09']);

    $this->actingAs($user)
        ->get(route('policies.export', ['status' => PolicyDisplayStatus::Upcoming->value]))
        ->assertOk();

    Excel::assertDownloaded('policies.xlsx', function (PoliciesExport $export) use ($upcoming) {
        $policies = $export->query()->get();

        return $policies->pluck('id')->all() === [$upcoming->id]
            && $export->map($policies->sole())[12] === 'Upcoming';
    });
});

test('an invalid status is rejected, including the stored-only active status', function (string $status) {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('policies.export', ['status' => $status]))
        ->assertInvalid(['status']);
})->with([
    'unknown' => 'unknown',
    'stored-only active' => 'active',
]);

test('a currency_id filter narrows the exported rows to that currency only', function () {
    Excel::fake();

    $user = User::factory()->withOrganization()->create();
    Policy::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    /** @var Policy $lbpPolicy */
    $lbpPolicy = Policy::factory()->forOrganization($user)->lbp()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('policies.export', ['currency_id' => $lbpPolicy->currency_id]))
        ->assertOk();

    Excel::assertDownloaded('policies.xlsx', function (PoliciesExport $export) use ($lbpPolicy) {
        return $export->query()->pluck('id')->all() === [$lbpPolicy->id];
    });
});

test('an amount bound without a currency is rejected', function (string $bound) {
    Excel::fake();

    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('policies.export', [$bound => 100]))
        ->assertInvalid([$bound]);
})->with(['amount_min', 'amount_max']);
