<?php

declare(strict_types=1);

use App\Actions\Tags\CreateTagAction;
use App\Models\Organization;
use App\Models\Scopes\CurrentOrganizationScope;
use App\Models\Tag;
use App\Models\User;
use App\Support\Tenancy\OrganizationContext;

test('creates a new tag when no matching name exists', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);

    /** @noinspection PhpUnhandledExceptionInspection */
    $tag = app(CreateTagAction::class)->handle($user, 'Medicare');

    expect($tag->name)->toBe('Medicare')
        ->and($tag->organization_id)->toBe($user->organization_id)
        ->and($tag->created_by)->toBe($user->id)
        ->and(Tag::query()->count())->toBe(1);
});

test('returns the existing tag when the name matches case-insensitively instead of creating a duplicate', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
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
    setOrganizationContext($user);

    /** @noinspection PhpUnhandledExceptionInspection */
    $tag = app(CreateTagAction::class)->handle($user, 'Medicare');

    expect($tag->organization_id)->toBe($user->organization_id)
        ->and(Tag::query()->withoutGlobalScope(CurrentOrganizationScope::class)->count())->toBe(2);
});

test('creates the tag scoped to the organization context rather than the user organization', function () {
    $user = User::factory()->withOrganization()->create();
    $otherOrganization = Organization::factory()->create();
    app(OrganizationContext::class)->set($otherOrganization->id);

    /** @noinspection PhpUnhandledExceptionInspection */
    $tag = app(CreateTagAction::class)->handle($user, 'Medicare');

    expect($tag->organization_id)->toBe($otherOrganization->id)
        ->and($tag->organization_id)->not->toBe($user->organization_id);
});
