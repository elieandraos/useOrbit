<?php

declare(strict_types=1);

use App\Http\Resources\ClientResource;
use App\Http\Resources\DocumentResource;
use App\Http\Resources\TagResource;
use App\Models\Client;
use App\Models\Document;
use App\Models\Organization;
use App\Models\Tag;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $client = Client::factory()->create();

    $this->get(route('clients.documents.index', $client))
        ->assertRedirect(route('login'));
});

test('authenticated user can list a client documents', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();
    Document::factory(2)->forOrganization($user)->create([
        'documentable_type' => $client->getMorphClass(),
        'documentable_id' => $client->id,
    ]);

    $this->actingAs($user)
        ->get(route('clients.documents.index', $client))
        ->assertOk()
        ->assertHasResource('client', ClientResource::make($client))
        ->assertHasResource(
            'documents',
            DocumentResource::collection($client->documents()->with(['uploadedBy', 'tags'])->latest()->get())
        );
});

test('documents from another client are not included', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();
    $otherClient = Client::factory()->forOrganization($user)->create();

    Document::factory(2)->forOrganization($user)->create([
        'documentable_type' => $client->getMorphClass(),
        'documentable_id' => $client->id,
    ]);
    Document::factory(3)->forOrganization($user)->create([
        'documentable_type' => $otherClient->getMorphClass(),
        'documentable_id' => $otherClient->id,
    ]);

    $this->actingAs($user)
        ->get(route('clients.documents.index', $client))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('documents', 2));
});

test('authenticated user gets 404 for a client from another organization', function () {
    $user = User::factory()->withOrganization()->create();

    $otherOrganization = Organization::factory()->create();
    $client = Client::factory()->for($otherOrganization)->create();

    $this->actingAs($user)
        ->get(route('clients.documents.index', $client))
        ->assertNotFound();
});

test('shares the organization tag catalog with usage counts', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create([
        'documentable_type' => $client->getMorphClass(),
        'documentable_id' => $client->id,
    ]);
    $document->tags()->attach($tag, ['organization_id' => $user->current_organization_id]);

    $this->actingAs($user)
        ->get(route('clients.documents.index', $client))
        ->assertOk()
        ->assertHasResource(
            'tags',
            TagResource::collection(
                Tag::query()
                    ->withTaggableCount($document->getMorphClass(), $client->getMorphClass())
                    ->orderBy('name')
                    ->get()
            )
        );
});

test('tag usage counts only reflect documents owned by clients', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create();

    $clientDocument = Document::factory()->forOrganization($user)->uploadedBy($user)->create([
        'documentable_type' => $client->getMorphClass(),
        'documentable_id' => $client->id,
    ]);
    $clientDocument->tags()->attach($tag, ['organization_id' => $user->current_organization_id]);

    $policyDocument = Document::factory()->forOrganization($user)->uploadedBy($user)->create([
        'documentable_type' => 'policies',
    ]);
    $policyDocument->tags()->attach($tag, ['organization_id' => $user->current_organization_id]);

    $this->actingAs($user)
        ->get(route('clients.documents.index', $client))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('tags.0.usage_count', 1));
});

test('tag catalog excludes tags from another organization', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();
    $otherUser = User::factory()->withOrganization()->create();
    Tag::factory()->forOrganization($otherUser)->createdBy($otherUser)->create();

    $this->actingAs($user)
        ->get(route('clients.documents.index', $client))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('tags', 0));
});

test('shares the document upload config for the dropzone', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();

    $this->actingAs($user)
        ->get(route('clients.documents.index', $client))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('uploadConfig.max_size_bytes', config('documents.max_size'))
            ->where('uploadConfig.max_files_per_batch', config('documents.max_files_per_batch'))
            ->has('uploadConfig.allowed_extensions', count(config('documents.allowed_mimes')))
        );
});
