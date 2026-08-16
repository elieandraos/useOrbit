<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\User;

test('createdBy resolves the user who created the client', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Client $client */
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    expect($client->createdBy)->toBeInstanceOf(User::class)
        ->and($client->createdBy->is($user))->toBeTrue();
});

test('documentableName returns the company name for a company client', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->company()->create(['created_by' => $user->id]);

    expect($client->documentableName())->toBe($client->company_name);
});

test('notificationSubjectName returns the company name for a company client', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->company()->create(['created_by' => $user->id]);

    expect($client->notificationSubjectName())->toBe($client->company_name);
});
