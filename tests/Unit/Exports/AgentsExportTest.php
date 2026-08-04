<?php

declare(strict_types=1);

use App\Enums\AgentStatus;
use App\Exports\AgentsExport;
use App\Models\Agent;

test('headings returns the export column labels', function () {
    $export = new AgentsExport([], null, 'asc');

    expect($export->headings())->toBe([
        'Name',
        'Phone',
        'Email',
        'Joined',
        'Clients',
        'Policies',
        'Status',
    ]);
});

test('map transforms an agent into an export row', function () {
    /** @var Agent $agent */
    $agent = Agent::factory()->create([
        'first_name' => 'Mira',
        'last_name' => 'Olsen',
        'phone' => '+96170123456',
        'email' => 'mira.olsen@useorbit.com',
        'joined_at' => '2020-06-01',
        'status' => AgentStatus::Active->value,
    ]);

    $export = new AgentsExport([], null, 'asc');

    expect($export->map($agent))->toBe([
        'Mira Olsen',
        '+96170123456',
        'mira.olsen@useorbit.com',
        'Jun 1, 2020',
        0,
        0,
        'Active',
    ]);
});
