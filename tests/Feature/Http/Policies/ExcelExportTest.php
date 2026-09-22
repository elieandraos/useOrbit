<?php

declare(strict_types=1);

use App\Exports\PoliciesExport;
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

test('a sort query param reorders the exported rows', function () {
    Excel::fake();

    $user = User::factory()->withOrganization()->create();

    /** @var Policy $bravo */
    $bravo = Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'policy_number' => 'POL-B']);
    /** @var Policy $alpha */
    $alpha = Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'policy_number' => 'POL-A']);

    $this->actingAs($user)
        ->get(route('policies.export', ['sort' => 'policy_number', 'direction' => 'asc']))
        ->assertOk();

    Excel::assertDownloaded('policies.xlsx', function (PoliciesExport $export) use ($alpha, $bravo) {
        return $export->query()->pluck('id')->all() === [$alpha->id, $bravo->id];
    });
});

test('an invalid status is rejected', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('policies.export', ['status' => 'unknown']))
        ->assertInvalid(['status']);
});
