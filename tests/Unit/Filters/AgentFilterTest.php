<?php

declare(strict_types=1);

use App\Enums\AgentStatus;
use App\Filters\AgentFilter;
use App\Models\Agent;

test('empty filters return the unfiltered builder', function () {
    Agent::factory(3)->create();

    /** @noinspection PhpUndefinedMethodInspection */
    $agents = Agent::query()->filter(new AgentFilter([]))->get();

    expect($agents)->toHaveCount(3);
});

test('a null or empty string value is skipped', function () {
    Agent::factory(3)->create();

    /** @noinspection PhpUndefinedMethodInspection */
    $agents = Agent::query()->filter(new AgentFilter(['search' => null]))->get();

    expect($agents)->toHaveCount(3);
});

test('an unrecognized filter key is ignored', function () {
    Agent::factory(3)->create();

    /** @noinspection PhpUndefinedMethodInspection */
    $agents = Agent::query()->filter(new AgentFilter(['unknown' => 'value']))->get();

    expect($agents)->toHaveCount(3);
});

test('search matches first name', function () {
    /** @var Agent $match */
    $match = Agent::factory()->create(['first_name' => 'Mira']);
    Agent::factory()->create(['first_name' => 'Nadia']);

    /** @noinspection PhpUndefinedMethodInspection */
    $agents = Agent::query()->filter(new AgentFilter(['search' => 'Mira']))->get();

    expect($agents->pluck('id')->all())->toBe([$match->id]);
});

test('search matches last name', function () {
    /** @var Agent $match */
    $match = Agent::factory()->create(['last_name' => 'Olsen']);
    Agent::factory()->create(['last_name' => 'Fares']);

    /** @noinspection PhpUndefinedMethodInspection */
    $agents = Agent::query()->filter(new AgentFilter(['search' => 'Olsen']))->get();

    expect($agents->pluck('id')->all())->toBe([$match->id]);
});

test('search matches phone', function () {
    /** @var Agent $match */
    $match = Agent::factory()->create(['phone' => '+96170123456']);
    Agent::factory()->create(['phone' => '+96170999999']);

    /** @noinspection PhpUndefinedMethodInspection */
    $agents = Agent::query()->filter(new AgentFilter(['search' => '70123456']))->get();

    expect($agents->pluck('id')->all())->toBe([$match->id]);
});

test('search matches email', function () {
    /** @var Agent $match */
    $match = Agent::factory()->create(['email' => 'mira.olsen@useorbit.com']);
    Agent::factory()->create(['email' => 'nadia.fares@useorbit.com']);

    /** @noinspection PhpUndefinedMethodInspection */
    $agents = Agent::query()->filter(new AgentFilter(['search' => 'mira.olsen']))->get();

    expect($agents->pluck('id')->all())->toBe([$match->id]);
});

test('search excludes non-matching agents', function () {
    Agent::factory()->create(['first_name' => 'Mira', 'last_name' => 'Olsen', 'phone' => '+96170123456', 'email' => 'mira@useorbit.com']);

    /** @noinspection PhpUndefinedMethodInspection */
    $agents = Agent::query()->filter(new AgentFilter(['search' => 'nonexistent']))->get();

    expect($agents)->toHaveCount(0);
});

test('archived=false returns only active agents', function () {
    /** @var Agent $active */
    $active = Agent::factory()->create(['status' => AgentStatus::Active->value]);
    Agent::factory()->archived()->create();

    /** @noinspection PhpUndefinedMethodInspection */
    $agents = Agent::query()->filter(new AgentFilter(['archived' => false]))->get();

    expect($agents->pluck('id')->all())->toBe([$active->id]);
});

test('archived=true returns only archived agents', function () {
    Agent::factory()->create(['status' => AgentStatus::Active->value]);
    /** @var Agent $archived */
    $archived = Agent::factory()->archived()->create();

    /** @noinspection PhpUndefinedMethodInspection */
    $agents = Agent::query()->filter(new AgentFilter(['archived' => true]))->get();

    expect($agents->pluck('id')->all())->toBe([$archived->id]);
});
