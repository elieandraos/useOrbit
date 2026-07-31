<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Document;
use App\Models\Tag;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('tags.index'))
        ->assertRedirect(route('login'));
});

test('owner_type is required', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('tags.index'))
        ->assertInvalid(['owner_type']);
});

test('owner_id is required', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('tags.index', ['owner_type' => 'clients']))
        ->assertInvalid(['owner_id']);
});

test('an owner_type that does not resolve via the morph map is rejected', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('tags.index', ['owner_type' => 'not-a-real-owner', 'owner_id' => 1]))
        ->assertInvalid(['owner_type']);
});

test('an owner_type that resolves via the morph map but does not implement Documentable is rejected', function () {
    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create();

    $this->actingAs($user)
        ->get(route('tags.index', ['owner_type' => 'documents', 'owner_id' => $document->id]))
        ->assertInvalid(['owner_type']);
});

test('an owner_id that does not belong to the current organization is rejected', function () {
    $user = User::factory()->withOrganization()->create();
    $otherClient = Client::factory()->create();

    $this->actingAs($user)
        ->get(route('tags.index', ['owner_type' => 'clients', 'owner_id' => $otherClient->id]))
        ->assertInvalid(['owner_id']);
});

test('tags from another organization are excluded', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();
    $otherUser = User::factory()->withOrganization()->create();

    $ownTag = Tag::factory()->forOrganization($user)->createdBy($user)->create();
    $ownDocument = Document::factory()->forOrganization($user)->uploadedBy($user)->create([
        'documentable_type' => $client->getMorphClass(),
        'documentable_id' => $client->id,
    ]);
    $ownDocument->tags()->attach($ownTag);

    $otherTag = Tag::factory()->forOrganization($otherUser)->createdBy($otherUser)->create();
    $otherDocument = Document::factory()->forOrganization($otherUser)->uploadedBy($otherUser)->create();
    $otherDocument->tags()->attach($otherTag);

    $response = $this->actingAs($user)
        ->get(route('tags.index', ['owner_type' => 'clients', 'owner_id' => $client->id]))
        ->assertOk();

    expect(collect($response->json())->pluck('id'))->toEqual(collect([$ownTag->id]));
});

test('a tag used only on a different owner type is included with a zero usage_count', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create();
    $policyDocument = Document::factory()->forOrganization($user)->uploadedBy($user)->create(['documentable_type' => 'policies']);
    $policyDocument->tags()->attach($tag);

    $response = $this->actingAs($user)
        ->get(route('tags.index', ['owner_type' => 'clients', 'owner_id' => $client->id]))
        ->assertOk();

    expect($response->json())->toHaveCount(1)
        ->and($response->json('0.usage_count'))->toBe(0);
});

test('a tag with no documents at all is included with a zero usage_count, matching the filter chips catalog', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();
    Tag::factory()->forOrganization($user)->createdBy($user)->create();

    $response = $this->actingAs($user)
        ->get(route('tags.index', ['owner_type' => 'clients', 'owner_id' => $client->id]))
        ->assertOk();

    expect($response->json())->toHaveCount(1)
        ->and($response->json('0.usage_count'))->toBe(0);
});

test('usage_count only reflects documents of the requested owner type', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create();

    $clientDocuments = Document::factory()->forOrganization($user)->uploadedBy($user)->count(2)->create([
        'documentable_type' => $client->getMorphClass(),
        'documentable_id' => $client->id,
    ]);
    $clientDocuments->each(fn (Document $document) => $document->tags()->attach($tag));

    $policyDocument = Document::factory()->forOrganization($user)->uploadedBy($user)->create(['documentable_type' => 'policies']);
    $policyDocument->tags()->attach($tag);

    $response = $this->actingAs($user)
        ->get(route('tags.index', ['owner_type' => 'clients', 'owner_id' => $client->id]))
        ->assertOk();

    expect($response->json('0.usage_count'))->toBe(2);
});

test('usage_count only reflects documents owned by the requested owner_id, not other clients', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();
    $otherClient = Client::factory()->forOrganization($user)->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create();

    $clientDocuments = Document::factory()->forOrganization($user)->uploadedBy($user)->count(2)->create([
        'documentable_type' => $client->getMorphClass(),
        'documentable_id' => $client->id,
    ]);
    $clientDocuments->each(fn (Document $document) => $document->tags()->attach($tag));

    $otherClientDocument = Document::factory()->forOrganization($user)->uploadedBy($user)->create([
        'documentable_type' => $otherClient->getMorphClass(),
        'documentable_id' => $otherClient->id,
    ]);
    $otherClientDocument->tags()->attach($tag);

    $response = $this->actingAs($user)
        ->get(route('tags.index', ['owner_type' => 'clients', 'owner_id' => $client->id]))
        ->assertOk();

    expect($response->json())->toHaveCount(1)
        ->and($response->json('0.usage_count'))->toBe(2);
});

test('tags are ordered alphabetically by name', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();

    $weekly = Tag::factory()->forOrganization($user)->createdBy($user)->create(['name' => 'Weekly']);
    $archived = Tag::factory()->forOrganization($user)->createdBy($user)->create(['name' => 'Archived']);
    $monthly = Tag::factory()->forOrganization($user)->createdBy($user)->create(['name' => 'Monthly']);

    foreach ([$weekly, $archived, $monthly] as $tag) {
        $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create([
            'documentable_type' => $client->getMorphClass(),
            'documentable_id' => $client->id,
        ]);
        $document->tags()->attach($tag);
    }

    $response = $this->actingAs($user)
        ->get(route('tags.index', ['owner_type' => 'clients', 'owner_id' => $client->id]))
        ->assertOk();

    expect(collect($response->json())->pluck('name'))->toEqual(collect(['Archived', 'Monthly', 'Weekly']));
});
