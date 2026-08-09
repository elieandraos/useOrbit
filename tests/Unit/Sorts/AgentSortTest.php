<?php

declare(strict_types=1);

use App\Models\Agent;
use App\Models\Scopes\CurrentOrganizationScope;
use App\Sorts\AgentSort;

test('name sorts by last name then first name ascending', function () {
    /** @var Agent $charlie */
    $charlie = Agent::factory()->create(['first_name' => 'Amy', 'last_name' => 'Charlie']);
    /** @var Agent $alpha */
    $alpha = Agent::factory()->create(['first_name' => 'Zoe', 'last_name' => 'Alpha']);
    /** @var Agent $bravo */
    $bravo = Agent::factory()->create(['first_name' => 'Mona', 'last_name' => 'Bravo']);

    /** @noinspection PhpUndefinedMethodInspection */
    $agents = Agent::query()->withoutGlobalScope(CurrentOrganizationScope::class)->sort(new AgentSort('name', 'asc'))->get();

    expect($agents->pluck('id')->all())->toBe([$alpha->id, $bravo->id, $charlie->id]);
});

test('name sort direction can be reversed', function () {
    /** @var Agent $alpha */
    $alpha = Agent::factory()->create(['last_name' => 'Alpha']);
    /** @var Agent $bravo */
    $bravo = Agent::factory()->create(['last_name' => 'Bravo']);

    /** @noinspection PhpUndefinedMethodInspection */
    $agents = Agent::query()->withoutGlobalScope(CurrentOrganizationScope::class)->sort(new AgentSort('name', 'desc'))->get();

    expect($agents->pluck('id')->all())->toBe([$bravo->id, $alpha->id]);
});

test('default falls back to last name then first name ascending', function () {
    /** @var Agent $alpha */
    $alpha = Agent::factory()->create(['last_name' => 'Alpha']);
    /** @var Agent $bravo */
    $bravo = Agent::factory()->create(['last_name' => 'Bravo']);

    /** @noinspection PhpUndefinedMethodInspection */
    $agents = Agent::query()->withoutGlobalScope(CurrentOrganizationScope::class)->sort(new AgentSort(null, 'asc'))->get();

    expect($agents->pluck('id')->all())->toBe([$alpha->id, $bravo->id]);
});

test('an unrecognized column falls back to the default', function () {
    /** @var Agent $alpha */
    $alpha = Agent::factory()->create(['last_name' => 'Alpha']);
    /** @var Agent $bravo */
    $bravo = Agent::factory()->create(['last_name' => 'Bravo']);

    /** @noinspection PhpUndefinedMethodInspection */
    $agents = Agent::query()->withoutGlobalScope(CurrentOrganizationScope::class)->sort(new AgentSort('unknown', 'asc'))->get();

    expect($agents->pluck('id')->all())->toBe([$alpha->id, $bravo->id]);
});
