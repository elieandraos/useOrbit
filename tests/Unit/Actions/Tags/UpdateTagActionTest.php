<?php

declare(strict_types=1);

use App\Actions\Tags\UpdateTagAction;
use App\Models\Tag;
use App\Models\User;

test('updates the tag name', function () {
    $user = User::factory()->withOrganization()->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create(['name' => 'Old name']);

    /** @noinspection PhpUnhandledExceptionInspection */
    $updated = app(UpdateTagAction::class)->handle($tag, 'New name');

    expect($updated->name)->toBe('New name');
    $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => 'New name']);
});

test('trims surrounding whitespace from the new name', function () {
    $user = User::factory()->withOrganization()->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create(['name' => 'Old name']);

    /** @noinspection PhpUnhandledExceptionInspection */
    $updated = app(UpdateTagAction::class)->handle($tag, '  Renamed  ');

    expect($updated->name)->toBe('Renamed');
});
