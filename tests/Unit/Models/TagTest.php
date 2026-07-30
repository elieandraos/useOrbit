<?php

declare(strict_types=1);

use App\Models\Document;
use App\Models\Tag;
use App\Models\TagAttachment;
use App\Models\User;
use Illuminate\Database\QueryException;

test('document tags resolve and round-trip through the taggables pivot', function () {
    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create();

    $document->tags()->attach($tag, ['organization_id' => $user->current_organization_id]);

    expect($document->fresh()->tags)->toHaveCount(1)
        ->and($document->fresh()->tags->first()->is($tag))->toBeTrue()
        ->and($tag->taggables()->where('taggable_id', $document->id)->where('taggable_type', $document->getMorphClass())->exists())->toBeTrue();
});

test('tag taggables counts attachments via withCount', function () {
    $user = User::factory()->withOrganization()->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create();
    $documents = Document::factory()->forOrganization($user)->uploadedBy($user)->count(2)->create();

    $documents->each(fn (Document $document) => $document->tags()->attach($tag, ['organization_id' => $user->current_organization_id]));

    $counted = Tag::query()->withCount('taggables')->findOrFail($tag->id);

    expect($counted->taggables_count)->toBe(2);
});

test('current organization scope only returns tags for the acting user\'s current organization', function () {
    $user = User::factory()->withOrganization()->create();
    $otherUser = User::factory()->withOrganization()->create();

    $ownTag = Tag::factory()->forOrganization($user)->createdBy($user)->create();
    Tag::factory()->forOrganization($otherUser)->createdBy($otherUser)->create();

    $this->actingAs($user);

    expect(Tag::query()->pluck('id'))->toEqual(collect([$ownTag->id]));
});

test('tag name is unique per organization at the database level', function () {
    $user = User::factory()->withOrganization()->create();
    Tag::factory()->forOrganization($user)->createdBy($user)->create(['name' => 'Urgent']);

    Tag::factory()->forOrganization($user)->createdBy($user)->create(['name' => 'Urgent']);
})->throws(QueryException::class);

test('relevantToTaggableType includes a tag with no taggables at all', function () {
    $user = User::factory()->withOrganization()->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create();

    expect(Tag::query()->relevantToTaggableType('documents')->pluck('id'))->toEqual(collect([$tag->id]));
});

test('relevantToTaggableType includes a tag used on the requested taggable type', function () {
    $user = User::factory()->withOrganization()->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create();
    $document->tags()->attach($tag, ['organization_id' => $user->current_organization_id]);

    expect(Tag::query()->relevantToTaggableType('documents')->pluck('id'))->toEqual(collect([$tag->id]));
});

test('relevantToTaggableType excludes a tag used only on a different taggable type', function () {
    $user = User::factory()->withOrganization()->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create();

    TagAttachment::query()->forceCreate([
        'organization_id' => $user->current_organization_id,
        'tag_id' => $tag->id,
        'taggable_type' => 'notes',
        'taggable_id' => 1,
    ]);

    expect(Tag::query()->relevantToTaggableType('documents')->pluck('id'))->toBeEmpty();
});
