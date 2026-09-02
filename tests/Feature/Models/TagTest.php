<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Document;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\QueryException;

test('document tags resolve and round-trip through the document_tag pivot', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create();

    $document->tags()->attach($tag);

    /** @var Document $document */
    $document = $document->fresh();

    expect($document->tags)->toHaveCount(1)
        ->and($document->tags->first()->is($tag))->toBeTrue()
        ->and($tag->documents()->where('document_id', $document->id)->exists())->toBeTrue();
});

test('withDocumentCount only counts documents belonging to the given owner', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create();
    $otherClient = Client::factory()->forOrganization($user)->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create();

    $clientDocuments = Document::factory()->forOrganization($user)->uploadedBy($user)->count(2)->create([
        'documentable_type' => $client->getMorphClass(),
        'documentable_id' => $client->id,
    ]);
    $clientDocuments->each(fn (Document $document) => $document->tags()->attach($tag));

    $otherDocument = Document::factory()->forOrganization($user)->uploadedBy($user)->create([
        'documentable_type' => $otherClient->getMorphClass(),
        'documentable_id' => $otherClient->id,
    ]);
    $otherDocument->tags()->attach($tag);

    /** @noinspection PhpUndefinedMethodInspection */
    $counted = Tag::query()->withDocumentCount($client->getMorphClass(), $client->id)->findOrFail($tag->id);

    expect($counted->documents_count)->toBe(2);
});

test('current organization scope only returns tags for the acting user\'s current organization', function () {
    $user = User::factory()->withOrganization()->create();
    $otherUser = User::factory()->withOrganization()->create();

    $ownTag = Tag::factory()->forOrganization($user)->createdBy($user)->create();
    Tag::factory()->forOrganization($otherUser)->createdBy($otherUser)->create();

    setOrganizationContext($user);

    expect(Tag::query()->pluck('id'))->toEqual(collect([$ownTag->id]));
});

test('createdBy resolves the user who created the tag', function () {
    $user = User::factory()->withOrganization()->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create();

    expect($tag->createdBy)->toBeInstanceOf(User::class)
        ->and($tag->createdBy->is($user))->toBeTrue();
});

test('updatedBy resolves the user who last updated the tag', function () {
    $user = User::factory()->withOrganization()->create();
    $updater = User::factory()->withOrganization()->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create(['updated_by' => $updater->id]);

    expect($tag->updatedBy)->toBeInstanceOf(User::class)
        ->and($tag->updatedBy->is($updater))->toBeTrue();
});

test('tag name is unique per organization at the database level', function () {
    $user = User::factory()->withOrganization()->create();
    Tag::factory()->forOrganization($user)->createdBy($user)->create(['name' => 'Urgent']);

    Tag::factory()->forOrganization($user)->createdBy($user)->create(['name' => 'Urgent']);
})->throws(QueryException::class);
