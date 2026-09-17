<?php

declare(strict_types=1);

use App\Enums\PolicyClass;
use App\Enums\PolicySource;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use App\Filters\PolicyFilter;
use App\Models\Carrier;
use App\Models\Policy;
use App\Models\Scopes\CurrentOrganizationScope;

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
    $match = Policy::factory()->create(['subclass' => 'Whole Life']);
    Policy::factory()->create(['subclass' => 'Term']);

    /** @noinspection PhpUndefinedMethodInspection */
    $policies = Policy::query()->withoutGlobalScope(CurrentOrganizationScope::class)->filter(new PolicyFilter(['search' => 'Whole']))->get();

    expect($policies->pluck('id')->all())->toBe([$match->id]);
});

test('search excludes non-matching policies', function () {
    Policy::factory()->create(['policy_number' => 'POL-1000', 'subclass' => 'Term']);

    /** @noinspection PhpUndefinedMethodInspection */
    $policies = Policy::query()->withoutGlobalScope(CurrentOrganizationScope::class)->filter(new PolicyFilter(['search' => 'nonexistent']))->get();

    expect($policies)->toHaveCount(0);
});

test('status narrows to the exact matching status only', function () {
    /** @var Policy $match */
    $match = Policy::factory()->create(['status' => PolicyStatus::Cancelled]);
    Policy::factory()->create(['status' => PolicyStatus::Active]);

    /** @noinspection PhpUndefinedMethodInspection */
    $policies = Policy::query()->withoutGlobalScope(CurrentOrganizationScope::class)->filter(new PolicyFilter(['status' => PolicyStatus::Cancelled->value]))->get();

    expect($policies->pluck('id')->all())->toBe([$match->id]);
});

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

test('amountMin is an inclusive lower bound', function () {
    /** @var Policy $onBoundary */
    $onBoundary = Policy::factory()->create(['premium_amount' => 500]);
    /** @var Policy $above */
    $above = Policy::factory()->create(['premium_amount' => 750]);
    Policy::factory()->create(['premium_amount' => 250]);

    /** @noinspection PhpUndefinedMethodInspection */
    $policies = Policy::query()->withoutGlobalScope(CurrentOrganizationScope::class)->filter(new PolicyFilter(['amount_min' => 500]))->get();

    expect($policies->pluck('id')->sort()->values()->all())->toBe(collect([$onBoundary->id, $above->id])->sort()->values()->all());
});

test('amountMax is an inclusive upper bound', function () {
    /** @var Policy $onBoundary */
    $onBoundary = Policy::factory()->create(['premium_amount' => 500]);
    /** @var Policy $below */
    $below = Policy::factory()->create(['premium_amount' => 250]);
    Policy::factory()->create(['premium_amount' => 750]);

    /** @noinspection PhpUndefinedMethodInspection */
    $policies = Policy::query()->withoutGlobalScope(CurrentOrganizationScope::class)->filter(new PolicyFilter(['amount_max' => 500]))->get();

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
    /** @var Carrier $carrier */
    $carrier = Carrier::factory()->create();

    /** @var Policy $match */
    $match = Policy::factory()->create([
        'policy_number' => 'POL-1000',
        'status' => PolicyStatus::Active,
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
        'premium_amount' => 9999,
    ]);

    /** @noinspection PhpUndefinedMethodInspection */
    $policies = Policy::query()->withoutGlobalScope(CurrentOrganizationScope::class)->filter(new PolicyFilter([
        'search' => '1000',
        'status' => PolicyStatus::Active->value,
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
