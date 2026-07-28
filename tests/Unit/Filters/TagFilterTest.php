<?php

declare(strict_types=1);

use App\Filters\TagFilter;
use App\Models\Document;
use App\Models\Tag;
use App\Models\User;

test('empty filters return the unfiltered builder', function () {
    Tag::factory(3)->create();

    /** @noinspection PhpUndefinedMethodInspection */
    $tags = Tag::query()->filter(new TagFilter([]))->get();

    expect($tags)->toHaveCount(3);
});

test('an unrecognized filter key is ignored', function () {
    Tag::factory(3)->create();

    /** @noinspection PhpUndefinedMethodInspection */
    $tags = Tag::query()->filter(new TagFilter(['unknown' => 'value']))->get();

    expect($tags)->toHaveCount(3);
});

test('documentableType includes a tag attached to a document of that owner type', function () {
    $user = User::factory()->withOrganization()->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create(['documentable_type' => 'clients']);
    $document->tags()->attach($tag, ['organization_id' => $user->current_organization_id]);

    /** @noinspection PhpUndefinedMethodInspection */
    $tags = Tag::query()->filter(new TagFilter(['documentable_type' => 'clients']))->get();

    expect($tags->pluck('id'))->toEqual(collect([$tag->id]));
});

test('documentableType excludes a tag attached only to a document of a different owner type', function () {
    $user = User::factory()->withOrganization()->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create(['documentable_type' => 'policies']);
    $document->tags()->attach($tag, ['organization_id' => $user->current_organization_id]);

    /** @noinspection PhpUndefinedMethodInspection */
    $tags = Tag::query()->filter(new TagFilter(['documentable_type' => 'clients']))->get();

    expect($tags)->toHaveCount(0);
});

test('documentableType excludes a tag with no taggables at all', function () {
    $user = User::factory()->withOrganization()->create();
    Tag::factory()->forOrganization($user)->createdBy($user)->create();

    /** @noinspection PhpUndefinedMethodInspection */
    $tags = Tag::query()->filter(new TagFilter(['documentable_type' => 'clients']))->get();

    expect($tags)->toHaveCount(0);
});

test('documentableType includes a tag shared across owner types only for the matching type', function () {
    $user = User::factory()->withOrganization()->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create();
    $clientDocument = Document::factory()->forOrganization($user)->uploadedBy($user)->create(['documentable_type' => 'clients']);
    $policyDocument = Document::factory()->forOrganization($user)->uploadedBy($user)->create(['documentable_type' => 'policies']);
    $clientDocument->tags()->attach($tag, ['organization_id' => $user->current_organization_id]);
    $policyDocument->tags()->attach($tag, ['organization_id' => $user->current_organization_id]);

    /** @noinspection PhpUndefinedMethodInspection */
    $clientTags = Tag::query()->filter(new TagFilter(['documentable_type' => 'clients']))->get();
    /** @noinspection PhpUndefinedMethodInspection */
    $policyTags = Tag::query()->filter(new TagFilter(['documentable_type' => 'policies']))->get();

    expect($clientTags->pluck('id'))->toEqual(collect([$tag->id]))
        ->and($policyTags->pluck('id'))->toEqual(collect([$tag->id]));
});
