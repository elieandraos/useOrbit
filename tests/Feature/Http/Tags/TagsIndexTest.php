<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Document;
use App\Models\Tag;
use App\Models\TagAttachment;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('tags.index'))
        ->assertRedirect(route('login'));
});

test('taggable_type is required', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('tags.index'))
        ->assertInvalid(['taggable_type']);
});

test('a taggable_type that does not resolve via the morph map is rejected', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('tags.index', ['taggable_type' => 'invoices']))
        ->assertInvalid(['taggable_type']);
});

test('a taggable_type that resolves via the morph map but does not implement Taggable is rejected', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('tags.index', ['taggable_type' => 'clients']))
        ->assertInvalid(['taggable_type']);
});

test('owner_type is required when the taggable type is polymorphically owned', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('tags.index', ['taggable_type' => 'documents']))
        ->assertInvalid(['owner_type']);
});

test('owner_id is required when the taggable type is polymorphically owned', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('tags.index', ['taggable_type' => 'documents', 'owner_type' => 'clients']))
        ->assertInvalid(['owner_id']);
});

test('an owner_type that does not resolve via the morph map is rejected', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('tags.index', ['taggable_type' => 'documents', 'owner_type' => 'not-a-real-owner']))
        ->assertInvalid(['owner_type']);
});

test('an owner_id that does not belong to the current organization is rejected', function () {
    $user = User::factory()->withOrganization()->create();
    $otherClient = Client::factory()->create();

    $this->actingAs($user)
        ->get(route('tags.index', ['taggable_type' => 'documents', 'owner_type' => 'clients', 'owner_id' => $otherClient->id]))
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
    $ownDocument->tags()->attach($ownTag, ['organization_id' => $user->current_organization_id]);

    $otherTag = Tag::factory()->forOrganization($otherUser)->createdBy($otherUser)->create();
    $otherDocument = Document::factory()->forOrganization($otherUser)->uploadedBy($otherUser)->create();
    $otherDocument->tags()->attach($otherTag, ['organization_id' => $otherUser->current_organization_id]);

    $response = $this->actingAs($user)
        ->get(route('tags.index', ['taggable_type' => 'documents', 'owner_type' => 'clients', 'owner_id' => $client->id]))
        ->assertOk();

    expect(collect($response->json())->pluck('id'))->toEqual(collect([$ownTag->id]));
});

test('a tag used only on a different owner type is included with a zero usage_count', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create();
    $policyDocument = Document::factory()->forOrganization($user)->uploadedBy($user)->create(['documentable_type' => 'policies']);
    $policyDocument->tags()->attach($tag, ['organization_id' => $user->current_organization_id]);

    $response = $this->actingAs($user)
        ->get(route('tags.index', ['taggable_type' => 'documents', 'owner_type' => 'clients', 'owner_id' => $client->id]))
        ->assertOk();

    expect($response->json())->toHaveCount(1)
        ->and($response->json('0.usage_count'))->toBe(0);
});

test('a tag with no taggables at all is included with a zero usage_count, matching the filter chips catalog', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();
    Tag::factory()->forOrganization($user)->createdBy($user)->create();

    $response = $this->actingAs($user)
        ->get(route('tags.index', ['taggable_type' => 'documents', 'owner_type' => 'clients', 'owner_id' => $client->id]))
        ->assertOk();

    expect($response->json())->toHaveCount(1)
        ->and($response->json('0.usage_count'))->toBe(0);
});

test('a tag used only on a different taggable type is excluded from the documents pool', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create();

    TagAttachment::query()->forceCreate([
        'organization_id' => $user->current_organization_id,
        'tag_id' => $tag->id,
        'taggable_type' => 'notes',
        'taggable_id' => 1,
    ]);

    $response = $this->actingAs($user)
        ->get(route('tags.index', ['taggable_type' => 'documents', 'owner_type' => 'clients', 'owner_id' => $client->id]))
        ->assertOk();

    expect($response->json())->toHaveCount(0);
});

test('usage_count only reflects documents of the requested owner type', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create();

    $clientDocuments = Document::factory()->forOrganization($user)->uploadedBy($user)->count(2)->create([
        'documentable_type' => $client->getMorphClass(),
        'documentable_id' => $client->id,
    ]);
    $clientDocuments->each(fn (Document $document) => $document->tags()->attach($tag, ['organization_id' => $user->current_organization_id]));

    $policyDocument = Document::factory()->forOrganization($user)->uploadedBy($user)->create(['documentable_type' => 'policies']);
    $policyDocument->tags()->attach($tag, ['organization_id' => $user->current_organization_id]);

    $response = $this->actingAs($user)
        ->get(route('tags.index', ['taggable_type' => 'documents', 'owner_type' => 'clients', 'owner_id' => $client->id]))
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
    $clientDocuments->each(fn (Document $document) => $document->tags()->attach($tag, ['organization_id' => $user->current_organization_id]));

    $otherClientDocument = Document::factory()->forOrganization($user)->uploadedBy($user)->create([
        'documentable_type' => $otherClient->getMorphClass(),
        'documentable_id' => $otherClient->id,
    ]);
    $otherClientDocument->tags()->attach($tag, ['organization_id' => $user->current_organization_id]);

    $response = $this->actingAs($user)
        ->get(route('tags.index', ['taggable_type' => 'documents', 'owner_type' => 'clients', 'owner_id' => $client->id]))
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
        $document->tags()->attach($tag, ['organization_id' => $user->current_organization_id]);
    }

    $response = $this->actingAs($user)
        ->get(route('tags.index', ['taggable_type' => 'documents', 'owner_type' => 'clients', 'owner_id' => $client->id]))
        ->assertOk();

    expect(collect($response->json())->pluck('name'))->toEqual(collect(['Archived', 'Monthly', 'Weekly']));
});
