<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Document;
use App\Models\User;
use App\Notifications\DocumentsUploadBatchProcessed;
use Illuminate\Notifications\Messages\BroadcastMessage;

test('is delivered via the database and broadcast channels', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();

    $notification = new DocumentsUploadBatchProcessed(['completed' => 1, 'failed' => 0], collect(), $client);

    expect($notification->via($user))->toBe(['database', 'broadcast']);
});

test('array and broadcast payloads carry counts, document details, client info, and a summary', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['first_name' => 'Jane', 'last_name' => 'Doe']);
    $completed = Document::factory()->forOrganization($user)->uploadedBy($user)->completed()->create([
        'documentable_type' => $client->getMorphClass(),
        'documentable_id' => $client->id,
    ]);
    $failed = Document::factory()->forOrganization($user)->uploadedBy($user)->failed()->create([
        'documentable_type' => $client->getMorphClass(),
        'documentable_id' => $client->id,
    ]);

    $notification = new DocumentsUploadBatchProcessed(
        ['completed' => 1, 'failed' => 1],
        collect([$completed, $failed]),
        $client,
    );

    $expected = [
        'total' => 2,
        'completed' => 1,
        'failed' => 1,
        'documents' => [
            ['id' => $completed->id, 'status' => 'completed'],
            ['id' => $failed->id, 'status' => 'failed'],
        ],
        'client' => ['slug' => $client->slug, 'name' => 'Jane Doe'],
        'summary' => '1 of 2 documents uploaded, 1 failed.',
    ];

    expect($notification->toArray($user))->toBe($expected);

    $broadcast = $notification->toBroadcast($user);
    expect($broadcast)->toBeInstanceOf(BroadcastMessage::class)
        ->and($broadcast->data)->toBe($expected);
});

test('summarizes an all-success batch without mentioning failures', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();
    $documents = Document::factory(2)->forOrganization($user)->uploadedBy($user)->completed()->create([
        'documentable_type' => $client->getMorphClass(),
        'documentable_id' => $client->id,
    ]);

    $notification = new DocumentsUploadBatchProcessed(['completed' => 2, 'failed' => 0], $documents, $client);

    expect($notification->toArray($user)['summary'])->toBe('2 of 2 documents uploaded successfully.');
});

test('summarizes an all-failed batch without mentioning successes', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();
    $documents = Document::factory(2)->forOrganization($user)->uploadedBy($user)->failed()->create([
        'documentable_type' => $client->getMorphClass(),
        'documentable_id' => $client->id,
    ]);

    $notification = new DocumentsUploadBatchProcessed(['completed' => 0, 'failed' => 2], $documents, $client);

    expect($notification->toArray($user)['summary'])->toBe('2 of 2 documents failed to upload.');
});
