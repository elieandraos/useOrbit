<?php

declare(strict_types=1);

use App\Models\Document;
use App\Models\Tag;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('tags.index'))
        ->assertRedirect(route('login'));
});

test('documentable_type is required', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('tags.index'))
        ->assertInvalid(['documentable_type']);
});

test('an unrecognized documentable_type is rejected', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('tags.index', ['documentable_type' => 'invoices']))
        ->assertInvalid(['documentable_type']);
});

test('tags from another organization are excluded', function () {
    $user = User::factory()->withOrganization()->create();
    $otherUser = User::factory()->withOrganization()->create();

    $ownTag = Tag::factory()->forOrganization($user)->createdBy($user)->create();
    $ownDocument = Document::factory()->forOrganization($user)->uploadedBy($user)->create();
    $ownDocument->tags()->attach($ownTag, ['organization_id' => $user->current_organization_id]);

    $otherTag = Tag::factory()->forOrganization($otherUser)->createdBy($otherUser)->create();
    $otherDocument = Document::factory()->forOrganization($otherUser)->uploadedBy($otherUser)->create();
    $otherDocument->tags()->attach($otherTag, ['organization_id' => $otherUser->current_organization_id]);

    $response = $this->actingAs($user)
        ->get(route('tags.index', ['documentable_type' => 'clients']))
        ->assertOk();

    expect(collect($response->json())->pluck('id'))->toEqual(collect([$ownTag->id]));
});

test('tags used only on a different owner type are excluded', function () {
    $user = User::factory()->withOrganization()->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create();
    $policyDocument = Document::factory()->forOrganization($user)->uploadedBy($user)->create(['documentable_type' => 'policies']);
    $policyDocument->tags()->attach($tag, ['organization_id' => $user->current_organization_id]);

    $response = $this->actingAs($user)
        ->get(route('tags.index', ['documentable_type' => 'clients']))
        ->assertOk();

    expect($response->json())->toHaveCount(0);
});

test('usage_count only reflects documents of the requested owner type', function () {
    $user = User::factory()->withOrganization()->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create();

    $clientDocuments = Document::factory()->forOrganization($user)->uploadedBy($user)->count(2)->create();
    $clientDocuments->each(fn (Document $document) => $document->tags()->attach($tag, ['organization_id' => $user->current_organization_id]));

    $policyDocument = Document::factory()->forOrganization($user)->uploadedBy($user)->create(['documentable_type' => 'policies']);
    $policyDocument->tags()->attach($tag, ['organization_id' => $user->current_organization_id]);

    $response = $this->actingAs($user)
        ->get(route('tags.index', ['documentable_type' => 'clients']))
        ->assertOk();

    expect($response->json('0.usage_count'))->toBe(2);
});

test('tags are ordered alphabetically by name', function () {
    $user = User::factory()->withOrganization()->create();

    $weekly = Tag::factory()->forOrganization($user)->createdBy($user)->create(['name' => 'Weekly']);
    $archived = Tag::factory()->forOrganization($user)->createdBy($user)->create(['name' => 'Archived']);
    $monthly = Tag::factory()->forOrganization($user)->createdBy($user)->create(['name' => 'Monthly']);

    foreach ([$weekly, $archived, $monthly] as $tag) {
        $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create();
        $document->tags()->attach($tag, ['organization_id' => $user->current_organization_id]);
    }

    $response = $this->actingAs($user)
        ->get(route('tags.index', ['documentable_type' => 'clients']))
        ->assertOk();

    expect(collect($response->json())->pluck('name'))->toEqual(collect(['Archived', 'Monthly', 'Weekly']));
});
