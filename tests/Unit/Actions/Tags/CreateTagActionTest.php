<?php

declare(strict_types=1);

use App\Actions\Tags\CreateTagAction;
use App\Models\Tag;
use App\Models\User;

test('creates a new tag when no matching name exists', function () {
    $user = User::factory()->withOrganization()->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $tag = app(CreateTagAction::class)->handle($user, 'Medicare');

    expect($tag->name)->toBe('Medicare')
        ->and($tag->organization_id)->toBe($user->organization_id)
        ->and($tag->created_by)->toBe($user->id)
        ->and(Tag::query()->count())->toBe(1);
});

test('returns the existing tag when the name matches case-insensitively instead of creating a duplicate', function () {
    $user = User::factory()->withOrganization()->create();
    $existing = Tag::factory()->forOrganization($user)->createdBy($user)->create(['name' => 'Medicare']);

    /** @noinspection PhpUnhandledExceptionInspection */
    $tag = app(CreateTagAction::class)->handle($user, 'medicare');

    expect($tag->is($existing))->toBeTrue()
        ->and(Tag::query()->count())->toBe(1);
});

test('scopes the case-insensitive match to the user current organization', function () {
    $user = User::factory()->withOrganization()->create();
    $otherUser = User::factory()->withOrganization()->create();
    Tag::factory()->forOrganization($otherUser)->createdBy($otherUser)->create(['name' => 'Medicare']);

    /** @noinspection PhpUnhandledExceptionInspection */
    $tag = app(CreateTagAction::class)->handle($user, 'Medicare');

    expect($tag->organization_id)->toBe($user->organization_id)
        ->and(Tag::query()->count())->toBe(2);
});
