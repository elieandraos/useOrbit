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

test('array and broadcast payloads carry the action, actor, subject, meta, and a summary', function () {
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
        'action' => 'documents.uploaded',
        'actor' => null,
        'subject' => ['kind' => 'client', 'slug' => $client->slug, 'name' => 'Jane Doe'],
        'meta' => [
            'total' => 2,
            'completed' => 1,
            'failed' => 1,
            'documents' => [
                ['id' => $completed->id, 'status' => 'completed'],
                ['id' => $failed->id, 'status' => 'failed'],
            ],
        ],
        'summary' => '1 of 2 documents uploaded to client Jane Doe — 1 failed.',
    ];

    expect($notification->toArray($user))->toBe($expected)
        ->and(DocumentsUploadBatchProcessed::ACTION)->toBe('documents.uploaded');

    $broadcast = $notification->toBroadcast($user);
    expect($broadcast)->toBeInstanceOf(BroadcastMessage::class)
        ->and($broadcast->data)->toBe($expected);
});

test('summarizes an all-success batch without mentioning failures', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['first_name' => 'Jane', 'last_name' => 'Doe']);
    $documents = Document::factory(2)->forOrganization($user)->uploadedBy($user)->completed()->create([
        'documentable_type' => $client->getMorphClass(),
        'documentable_id' => $client->id,
    ]);

    $notification = new DocumentsUploadBatchProcessed(['completed' => 2, 'failed' => 0], $documents, $client);

    expect($notification->toArray($user)['summary'])->toBe('2 documents uploaded to client Jane Doe.');
});

test('summarizes a single-document upload using the singular form', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['first_name' => 'Jane', 'last_name' => 'Doe']);
    $documents = Document::factory(1)->forOrganization($user)->uploadedBy($user)->completed()->create([
        'documentable_type' => $client->getMorphClass(),
        'documentable_id' => $client->id,
    ]);

    $notification = new DocumentsUploadBatchProcessed(['completed' => 1, 'failed' => 0], $documents, $client);

    expect($notification->toArray($user)['summary'])->toBe('1 document uploaded to client Jane Doe.');
});

test('summarizes an all-failed batch without mentioning successes', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['first_name' => 'Jane', 'last_name' => 'Doe']);
    $documents = Document::factory(2)->forOrganization($user)->uploadedBy($user)->failed()->create([
        'documentable_type' => $client->getMorphClass(),
        'documentable_id' => $client->id,
    ]);

    $notification = new DocumentsUploadBatchProcessed(['completed' => 0, 'failed' => 2], $documents, $client);

    expect($notification->toArray($user)['summary'])->toBe('2 documents failed to upload to client Jane Doe.');
});

test('attributes an all-success summary to the actor when a member acts on behalf of the recipient', function () {
    $user = User::factory()->withOrganization()->create();
    $actor = User::factory()->create(['name' => 'Sarah Cohen']);
    $client = Client::factory()->forOrganization($user)->create(['first_name' => 'Jane', 'last_name' => 'Doe']);
    $documents = Document::factory(2)->forOrganization($user)->uploadedBy($actor)->completed()->create([
        'documentable_type' => $client->getMorphClass(),
        'documentable_id' => $client->id,
    ]);

    $notification = new DocumentsUploadBatchProcessed(['completed' => 2, 'failed' => 0], $documents, $client, $actor);

    $data = $notification->toArray($user);

    expect($data['actor'])->toBe(['id' => $actor->id, 'name' => 'Sarah Cohen'])
        ->and($data['summary'])->toBe('Sarah Cohen uploaded 2 documents to client Jane Doe.');
});

test('attributes an all-failed summary to the actor without mentioning successes', function () {
    $user = User::factory()->withOrganization()->create();
    $actor = User::factory()->create(['name' => 'Sarah Cohen']);
    $client = Client::factory()->forOrganization($user)->create(['first_name' => 'Jane', 'last_name' => 'Doe']);
    $documents = Document::factory(2)->forOrganization($user)->uploadedBy($actor)->failed()->create([
        'documentable_type' => $client->getMorphClass(),
        'documentable_id' => $client->id,
    ]);

    $notification = new DocumentsUploadBatchProcessed(['completed' => 0, 'failed' => 2], $documents, $client, $actor);

    expect($notification->toArray($user)['summary'])
        ->toBe('Sarah Cohen failed to upload 2 documents to client Jane Doe.');
});

test('attributes a mixed summary to the actor with the failure count', function () {
    $user = User::factory()->withOrganization()->create();
    $actor = User::factory()->create(['name' => 'Sarah Cohen']);
    $client = Client::factory()->forOrganization($user)->create(['first_name' => 'Jane', 'last_name' => 'Doe']);
    $completed = Document::factory()->forOrganization($user)->uploadedBy($actor)->completed()->create([
        'documentable_type' => $client->getMorphClass(),
        'documentable_id' => $client->id,
    ]);
    $failed = Document::factory()->forOrganization($user)->uploadedBy($actor)->failed()->create([
        'documentable_type' => $client->getMorphClass(),
        'documentable_id' => $client->id,
    ]);

    $notification = new DocumentsUploadBatchProcessed(
        ['completed' => 1, 'failed' => 1],
        collect([$completed, $failed]),
        $client,
        $actor,
    );

    expect($notification->toArray($user)['summary'])
        ->toBe('Sarah Cohen uploaded 1 of 2 documents to client Jane Doe — 1 failed.');
});
