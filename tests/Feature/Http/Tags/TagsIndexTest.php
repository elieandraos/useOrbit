<?php

declare(strict_types=1);

use App\Models\Document;
use App\Models\Tag;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('tags.index'))
        ->assertRedirect(route('login'));
});

test('tags from another organization are excluded', function () {
    $user = User::factory()->withOrganization()->create();
    $otherUser = User::factory()->withOrganization()->create();

    $ownTag = Tag::factory()->forOrganization($user)->createdBy($user)->create();
    Tag::factory()->forOrganization($otherUser)->createdBy($otherUser)->create();

    $response = $this->actingAs($user)
        ->get(route('tags.index'))
        ->assertOk();

    expect(collect($response->json())->pluck('id'))->toEqual(collect([$ownTag->id]));
});

test('usage_count reflects the number of attached documents', function () {
    $user = User::factory()->withOrganization()->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create();
    $documents = Document::factory()->forOrganization($user)->uploadedBy($user)->count(2)->create();

    $documents->each(fn (Document $document) => $document->tags()->attach($tag, ['organization_id' => $user->current_organization_id]));

    $response = $this->actingAs($user)
        ->get(route('tags.index'))
        ->assertOk();

    expect($response->json('0.usage_count'))->toBe(2);
});

test('tags are ordered alphabetically by name', function () {
    $user = User::factory()->withOrganization()->create();

    Tag::factory()->forOrganization($user)->createdBy($user)->create(['name' => 'Weekly']);
    Tag::factory()->forOrganization($user)->createdBy($user)->create(['name' => 'Archived']);
    Tag::factory()->forOrganization($user)->createdBy($user)->create(['name' => 'Monthly']);

    $response = $this->actingAs($user)
        ->get(route('tags.index'))
        ->assertOk();

    expect(collect($response->json())->pluck('name'))->toEqual(collect(['Archived', 'Monthly', 'Weekly']));
});
