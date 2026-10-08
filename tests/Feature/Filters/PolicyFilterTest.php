<?php

declare(strict_types=1);

use App\Enums\PolicyClass;
use App\Enums\PolicyDisplayStatus;
use App\Enums\PolicySource;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use App\Filters\PolicyFilter;
use App\Http\Resources\PolicyResource;
use App\Models\Carrier;
use App\Models\Policy;
use App\Models\Scopes\CurrentOrganizationScope;
use App\Models\User;
use Carbon\CarbonImmutable;

test('empty filters return the unfiltered builder', function () {
    Policy::factory(3)->create();

    /** @noinspection PhpUndefinedMethodInspection */
    $policies = Policy::query()->withoutGlobalScope(CurrentOrganizationScope::class)->filter(new PolicyFilter([]))->get();

    expect($policies)->toHaveCount(3);
});

test('a null or empty string value is skipped', function () {
    Policy::factory(3)->create();

    /** @noinspection PhpUndefinedMethodInspection */
    $policies = Policy::query()->withoutGlobalScope(CurrentOrganizationScope::class)->filter(new PolicyFilter(['search' => null, 'status' => '']))->get();

    expect($policies)->toHaveCount(3);
});

test('an unrecognized filter key is ignored', function () {
    Policy::factory(3)->create();

    /** @noinspection PhpUndefinedMethodInspection */
    $policies = Policy::query()->withoutGlobalScope(CurrentOrganizationScope::class)->filter(new PolicyFilter(['unknown' => 'value']))->get();

    expect($policies)->toHaveCount(3);
});

test('no status filter leaves every status visible', function () {
    Policy::factory()->create(['status' => PolicyStatus::Active]);
    Policy::factory()->create(['status' => PolicyStatus::Cancelled]);
    Policy::factory()->create(['status' => PolicyStatus::Frozen]);

    /** @noinspection PhpUndefinedMethodInspection */
    $policies = Policy::query()->withoutGlobalScope(CurrentOrganizationScope::class)->filter(new PolicyFilter([]))->get();

    expect($policies)->toHaveCount(3);
});

test('search matches policy number', function () {
    /** @var Policy $match */
    $match = Policy::factory()->create(['policy_number' => 'POL-1000']);
    Policy::factory()->create(['policy_number' => 'POL-2000']);

    /** @noinspection PhpUndefinedMethodInspection */
    $policies = Policy::query()->withoutGlobalScope(CurrentOrganizationScope::class)->filter(new PolicyFilter(['search' => '1000']))->get();

    expect($policies->pluck('id')->all())->toBe([$match->id]);
});

test('search matches subclass', function () {
    /** @var Policy $match */
    $match = Policy::factory()->create(['class' => 'life', 'subclass' => 'Whole life']);
    Policy::factory()->create(['class' => 'life', 'subclass' => 'Term']);

    /** @noinspection PhpUndefinedMethodInspection */
    $policies = Policy::query()->withoutGlobalScope(CurrentOrganizationScope::class)->filter(new PolicyFilter(['search' => 'Whole']))->get();

    expect($policies->pluck('id')->all())->toBe([$match->id]);
});

test('search excludes non-matching policies', function () {
    Policy::factory()->create(['policy_number' => 'POL-1000', 'class' => 'life', 'subclass' => 'Term']);

    /** @noinspection PhpUndefinedMethodInspection */
    $policies = Policy::query()->withoutGlobalScope(CurrentOrganizationScope::class)->filter(new PolicyFilter(['search' => 'nonexistent']))->get();

    expect($policies)->toHaveCount(0);
});

dataset('display status boundary days', [
    'effective today' => [PolicyStatus::Active, 0, 365, PolicyDisplayStatus::InForce],
    'expiry today' => [PolicyStatus::Active, -365, 0, PolicyDisplayStatus::InForce],
    'single-day term' => [PolicyStatus::Active, 0, 0, PolicyDisplayStatus::InForce],
    'effective tomorrow' => [PolicyStatus::Active, 1, 366, PolicyDisplayStatus::Upcoming],
    'expiry yesterday' => [PolicyStatus::Active, -366, -1, PolicyDisplayStatus::Expired],
    'cancelled while in force' => [PolicyStatus::Cancelled, 0, 0, PolicyDisplayStatus::Cancelled],
    'cancelled before the term' => [PolicyStatus::Cancelled, 1, 366, PolicyDisplayStatus::Cancelled],
    'frozen after the term' => [PolicyStatus::Frozen, -366, -1, PolicyDisplayStatus::Frozen],
    'frozen while in force' => [PolicyStatus::Frozen, -1, 1, PolicyDisplayStatus::Frozen],
]);

dataset('organization timezones', [
    'UTC fallback' => [null, '2026-03-10'],
    'Asia/Tokyo override, a day ahead of UTC' => ['Asia/Tokyo', '2026-03-11'],
]);

/**
 * Create a policy in an organization with the given timezone, and make it the current organization.
 *
 * @param  array<string, mixed>  $attributes
 */
function policyInOrganizationWithTimezone(?string $timezone, array $attributes): Policy
{
    $user = User::factory()->withOrganization()->create();
    $user->organization->update(['timezone' => $timezone]);
    setOrganizationContext($user);

    return Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, ...$attributes])->fresh();
}

/**
 * @return array<int, int>
 */
function policyIdsWithDisplayStatus(PolicyDisplayStatus $displayStatus): array
{
    /** @noinspection PhpUndefinedMethodInspection */
    return Policy::query()->filter(new PolicyFilter(['status' => $displayStatus->value]))->pluck('id')->all();
}

test('the resource value and the status filter agree on every boundary day', function (PolicyStatus $status, int $effectiveOffset, int $expiryOffset, PolicyDisplayStatus $expected, ?string $timezone, string $localToday) {
    $this->travelTo('2026-03-10 20:00:00');
    $today = CarbonImmutable::parse($localToday);
    $policy = policyInOrganizationWithTimezone($timezone, [
        'status' => $status,
        'effective_date' => $today->addDays($effectiveOffset)->toDateString(),
        'expiry_date' => $today->addDays($expiryOffset)->toDateString(),
    ]);

    expect(PolicyResource::make($policy)->resolve()['display_status'])->toBe($expected);

    foreach (PolicyDisplayStatus::cases() as $displayStatus) {
        expect(policyIdsWithDisplayStatus($displayStatus))->toBe($displayStatus === $expected ? [$policy->id] : []);
    }
})->with('display status boundary days')->with('organization timezones');

test('both paths flip together at the organization-local midnight, not the UTC one', function (string $instant, string $timezone, PolicyDisplayStatus $expected, PolicyDisplayStatus $other) {
    $this->travelTo($instant);
    $policy = policyInOrganizationWithTimezone($timezone, [
        'status' => PolicyStatus::Active,
        'effective_date' => '2025-01-16',
        'expiry_date' => '2026-01-15',
    ]);

    expect(PolicyResource::make($policy)->resolve()['display_status'])->toBe($expected)
        ->and(policyIdsWithDisplayStatus($expected))->toBe([$policy->id])
        ->and(policyIdsWithDisplayStatus($other))->toBe([]);
})->with([
    'Beirut, one second before local midnight' => ['2026-01-15 21:59:59', 'Asia/Beirut', PolicyDisplayStatus::InForce, PolicyDisplayStatus::Expired],
    'Beirut, local midnight while UTC is still on the expiry day' => ['2026-01-15 22:00:00', 'Asia/Beirut', PolicyDisplayStatus::Expired, PolicyDisplayStatus::InForce],
    'New York, UTC already past the expiry day' => ['2026-01-16 03:00:00', 'America/New_York', PolicyDisplayStatus::InForce, PolicyDisplayStatus::Expired],
]);

test('type narrows to the exact matching type only', function () {
    /** @var Policy $match */
    $match = Policy::factory()->create(['type' => PolicyType::Group]);
    Policy::factory()->create(['type' => PolicyType::Single]);

    /** @noinspection PhpUndefinedMethodInspection */
    $policies = Policy::query()->withoutGlobalScope(CurrentOrganizationScope::class)->filter(new PolicyFilter(['type' => PolicyType::Group->value]))->get();

    expect($policies->pluck('id')->all())->toBe([$match->id]);
});

test('class narrows to any of the selected classes', function () {
    /** @var Policy $fire */
    $fire = Policy::factory()->create(['class' => PolicyClass::Fire]);
    /** @var Policy $life */
    $life = Policy::factory()->create(['class' => PolicyClass::Life]);
    Policy::factory()->create(['class' => PolicyClass::Travel]);

    /** @noinspection PhpUndefinedMethodInspection */
    $policies = Policy::query()->withoutGlobalScope(CurrentOrganizationScope::class)
        ->filter(new PolicyFilter(['class' => [PolicyClass::Fire->value, PolicyClass::Life->value]]))
        ->get();

    expect($policies->pluck('id')->sort()->values()->all())->toBe(collect([$fire->id, $life->id])->sort()->values()->all());
});

test('an empty class selection leaves the builder unfiltered', function () {
    Policy::factory(3)->create();

    /** @noinspection PhpUndefinedMethodInspection */
    $policies = Policy::query()->withoutGlobalScope(CurrentOrganizationScope::class)->filter(new PolicyFilter(['class' => []]))->get();

    expect($policies)->toHaveCount(3);
});

test('carrierId narrows to the exact matching carrier only', function () {
    /** @var Carrier $carrier */
    $carrier = Carrier::factory()->create();
    /** @var Policy $match */
    $match = Policy::factory()->create(['carrier_id' => $carrier->id]);
    Policy::factory()->create();

    /** @noinspection PhpUndefinedMethodInspection */
    $policies = Policy::query()->withoutGlobalScope(CurrentOrganizationScope::class)->filter(new PolicyFilter(['carrier_id' => $carrier->id]))->get();

    expect($policies->pluck('id')->all())->toBe([$match->id]);
});

test('currencyId narrows to the exact matching currency only', function () {
    /** @var Policy $match */
    $match = Policy::factory()->lbp()->create();
    Policy::factory()->create();

    /** @noinspection PhpUndefinedMethodInspection */
    $policies = Policy::query()->withoutGlobalScope(CurrentOrganizationScope::class)->filter(new PolicyFilter(['currency_id' => $match->currency_id]))->get();

    expect($policies->pluck('id')->all())->toBe([$match->id]);
});

test('source narrows to the exact matching source only', function () {
    /** @var Policy $match */
    $match = Policy::factory()->create(['source' => PolicySource::Agent]);
    Policy::factory()->create(['source' => PolicySource::Owner]);

    /** @noinspection PhpUndefinedMethodInspection */
    $policies = Policy::query()->withoutGlobalScope(CurrentOrganizationScope::class)->filter(new PolicyFilter(['source' => PolicySource::Agent->value]))->get();

    expect($policies->pluck('id')->all())->toBe([$match->id]);
});

test('effectiveFrom is an inclusive lower bound', function () {
    /** @var Policy $onBoundary */
    $onBoundary = Policy::factory()->create(['effective_date' => '2024-01-10']);
    /** @var Policy $after */
    $after = Policy::factory()->create(['effective_date' => '2024-01-15']);
    Policy::factory()->create(['effective_date' => '2024-01-05']);

    /** @noinspection PhpUndefinedMethodInspection */
    $policies = Policy::query()->withoutGlobalScope(CurrentOrganizationScope::class)->filter(new PolicyFilter(['effective_from' => '2024-01-10']))->get();

    expect($policies->pluck('id')->sort()->values()->all())->toBe(collect([$onBoundary->id, $after->id])->sort()->values()->all());
});

test('effectiveTo is an inclusive upper bound', function () {
    /** @var Policy $onBoundary */
    $onBoundary = Policy::factory()->create(['effective_date' => '2024-01-10']);
    /** @var Policy $before */
    $before = Policy::factory()->create(['effective_date' => '2024-01-05']);
    Policy::factory()->create(['effective_date' => '2024-01-15']);

    /** @noinspection PhpUndefinedMethodInspection */
    $policies = Policy::query()->withoutGlobalScope(CurrentOrganizationScope::class)->filter(new PolicyFilter(['effective_to' => '2024-01-10']))->get();

    expect($policies->pluck('id')->sort()->values()->all())->toBe(collect([$onBoundary->id, $before->id])->sort()->values()->all());
});

test('effectiveFrom and effectiveTo combined narrow to the inclusive range', function () {
    /** @var Policy $inRange */
    $inRange = Policy::factory()->create(['effective_date' => '2024-01-10']);
    Policy::factory()->create(['effective_date' => '2024-01-01']);
    Policy::factory()->create(['effective_date' => '2024-02-01']);

    /** @noinspection PhpUndefinedMethodInspection */
    $policies = Policy::query()->withoutGlobalScope(CurrentOrganizationScope::class)->filter(new PolicyFilter([
        'effective_from' => '2024-01-05',
        'effective_to' => '2024-01-20',
    ]))->get();

    expect($policies->pluck('id')->all())->toBe([$inRange->id]);
});

test('amountMin is an inclusive lower bound on the net premium', function () {
    /** @var Policy $onBoundary */
    $onBoundary = Policy::factory()->create(['premium_amount' => 700, 'discount_amount' => 200]);
    /** @var Policy $above */
    $above = Policy::factory()->create(['premium_amount' => 750, 'discount_amount' => 0]);
    Policy::factory()->create(['premium_amount' => 600, 'discount_amount' => 150]);

    /** @noinspection PhpUndefinedMethodInspection */
    $policies = Policy::query()->withoutGlobalScope(CurrentOrganizationScope::class)->filter(new PolicyFilter(['amount_min' => '500']))->get();

    expect($policies->pluck('id')->sort()->values()->all())->toBe(collect([$onBoundary->id, $above->id])->sort()->values()->all());
});

test('amountMax is an inclusive upper bound on the net premium', function () {
    /** @var Policy $onBoundary */
    $onBoundary = Policy::factory()->create(['premium_amount' => 700, 'discount_amount' => 200]);
    /** @var Policy $below */
    $below = Policy::factory()->create(['premium_amount' => 250, 'discount_amount' => 0]);
    Policy::factory()->create(['premium_amount' => 750, 'discount_amount' => 100]);

    /** @noinspection PhpUndefinedMethodInspection */
    $policies = Policy::query()->withoutGlobalScope(CurrentOrganizationScope::class)->filter(new PolicyFilter(['amount_max' => '500']))->get();

    expect($policies->pluck('id')->sort()->values()->all())->toBe(collect([$onBoundary->id, $below->id])->sort()->values()->all());
});

test('amountMin and amountMax combined narrow to the inclusive range', function () {
    /** @var Policy $inRange */
    $inRange = Policy::factory()->create(['premium_amount' => 500]);
    Policy::factory()->create(['premium_amount' => 100]);
    Policy::factory()->create(['premium_amount' => 900]);

    /** @noinspection PhpUndefinedMethodInspection */
    $policies = Policy::query()->withoutGlobalScope(CurrentOrganizationScope::class)->filter(new PolicyFilter([
        'amount_min' => 400,
        'amount_max' => 600,
    ]))->get();

    expect($policies->pluck('id')->all())->toBe([$inRange->id]);
});

test('all filters combined narrow to a single matching policy', function () {
    $this->travelTo('2024-06-01 12:00:00');
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);

    /** @var Carrier $carrier */
    $carrier = Carrier::factory()->create();

    /** @var Policy $match */
    $match = Policy::factory()->create([
        'policy_number' => 'POL-1000',
        'status' => PolicyStatus::Active,
        'expiry_date' => '2025-01-09',
        'type' => PolicyType::Group,
        'class' => PolicyClass::Fire,
        'carrier_id' => $carrier->id,
        'source' => PolicySource::Agent,
        'effective_date' => '2024-01-10',
        'premium_amount' => 500,
    ]);

    Policy::factory()->create([
        'policy_number' => 'POL-1000',
        'status' => PolicyStatus::Active,
        'type' => PolicyType::Group,
        'class' => PolicyClass::Fire,
        'carrier_id' => $carrier->id,
        'source' => PolicySource::Agent,
        'effective_date' => '2024-01-10',
        'expiry_date' => '2025-01-09',
        'premium_amount' => 9999,
    ]);

    /** @noinspection PhpUndefinedMethodInspection */
    $policies = Policy::query()->withoutGlobalScope(CurrentOrganizationScope::class)->filter(new PolicyFilter([
        'search' => '1000',
        'status' => PolicyDisplayStatus::InForce->value,
        'type' => PolicyType::Group->value,
        'class' => [PolicyClass::Fire->value],
        'carrier_id' => $carrier->id,
        'source' => PolicySource::Agent->value,
        'effective_from' => '2024-01-01',
        'effective_to' => '2024-01-31',
        'amount_min' => 400,
        'amount_max' => 600,
    ]))->get();

    expect($policies->pluck('id')->all())->toBe([$match->id]);
});
