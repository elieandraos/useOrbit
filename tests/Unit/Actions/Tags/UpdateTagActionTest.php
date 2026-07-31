<?php

declare(strict_types=1);

use App\Actions\Tags\UpdateTagAction;
use App\Models\Tag;
use App\Models\User;

test('updates the tag name', function () {
    $user = User::factory()->withOrganization()->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create(['name' => 'Old name']);

    /** @noinspection PhpUnhandledExceptionInspection */
    $updated = app(UpdateTagAction::class)->handle($user, $tag, 'New name');

    expect($updated->name)->toBe('New name');
    $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => 'New name']);
});

test('trims surrounding whitespace from the new name', function () {
    $user = User::factory()->withOrganization()->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create(['name' => 'Old name']);

    /** @noinspection PhpUnhandledExceptionInspection */
    $updated = app(UpdateTagAction::class)->handle($user, $tag, '  Renamed  ');

    expect($updated->name)->toBe('Renamed');
});

test('sets updated_by to the user id', function () {
    $user = User::factory()->withOrganization()->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdateTagAction::class)->handle($user, $tag, 'New name');

    expect($tag->fresh()->updated_by)->toBe($user->id);
});
