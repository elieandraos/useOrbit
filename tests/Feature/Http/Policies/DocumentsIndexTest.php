<?php

declare(strict_types=1);

use App\Http\Resources\DocumentResource;
use App\Http\Resources\PolicyMedicalResource;
use App\Http\Resources\TagResource;
use App\Models\Document;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\Tag;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $policy = Policy::factory()->create();

    $this->get(route('policies.documents.index', $policy))
        ->assertRedirect(route('login'));
});

test('authenticated user can list a policy documents', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create();
    Document::factory(2)->forOrganization($user)->create([
        'documentable_type' => $policy->getMorphClass(),
        'documentable_id' => $policy->id,
    ]);

    $this->actingAs($user)
        ->get(route('policies.documents.index', $policy))
        ->assertOk()
        ->assertHasResource('policy', PolicyMedicalResource::make($policy->load(['client', 'carrier', 'agent'])))
        ->assertHasResource(
            'documents',
            DocumentResource::collection(
                $policy->documents()->with(['uploadedBy', 'tags'])->latest()->orderByDesc('id')->get()
            )
        );
});

test('documents from another policy are not included', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create();
    $otherPolicy = Policy::factory()->forOrganization($user)->medical()->create();

    Document::factory(2)->forOrganization($user)->create([
        'documentable_type' => $policy->getMorphClass(),
        'documentable_id' => $policy->id,
    ]);
    Document::factory(3)->forOrganization($user)->create([
        'documentable_type' => $otherPolicy->getMorphClass(),
        'documentable_id' => $otherPolicy->id,
    ]);

    $this->actingAs($user)
        ->get(route('policies.documents.index', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('documents', 2));
});

test('authenticated user gets 404 for a policy from another organization', function () {
    $user = User::factory()->withOrganization()->create();

    $otherOrganization = Organization::factory()->create();
    $policy = Policy::factory()->for($otherOrganization)->create();

    $this->actingAs($user)
        ->get(route('policies.documents.index', $policy))
        ->assertNotFound();
});

test('shares the organization tag catalog with usage counts', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create([
        'documentable_type' => $policy->getMorphClass(),
        'documentable_id' => $policy->id,
    ]);
    $document->tags()->attach($tag);

    setOrganizationContext($user);
    /** @noinspection PhpUndefinedMethodInspection */
    $tags = Tag::query()
        ->withDocumentCount($policy->getMorphClass(), $policy->id)
        ->orderBy('name')
        ->get();

    $this->actingAs($user)
        ->get(route('policies.documents.index', $policy))
        ->assertOk()
        ->assertHasResource('tags', TagResource::collection($tags));
});

test('tag usage counts only reflect documents owned by policies', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create();

    $policyDocument = Document::factory()->forOrganization($user)->uploadedBy($user)->create([
        'documentable_type' => $policy->getMorphClass(),
        'documentable_id' => $policy->id,
    ]);
    $policyDocument->tags()->attach($tag);

    $clientDocument = Document::factory()->forOrganization($user)->uploadedBy($user)->create([
        'documentable_type' => 'clients',
    ]);
    $clientDocument->tags()->attach($tag);

    $this->actingAs($user)
        ->get(route('policies.documents.index', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('tags.0.usage_count', 1));
});

test('tag usage counts are scoped to the policy being viewed, not leaked from other policies', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create();
    $otherPolicy = Policy::factory()->forOrganization($user)->medical()->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create();

    $otherPolicyDocument = Document::factory()->forOrganization($user)->uploadedBy($user)->create([
        'documentable_type' => $otherPolicy->getMorphClass(),
        'documentable_id' => $otherPolicy->id,
    ]);
    $otherPolicyDocument->tags()->attach($tag);

    $this->actingAs($user)
        ->get(route('policies.documents.index', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('tags.0.usage_count', 0));
});

test('tag catalog excludes tags from another organization', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create();
    $otherUser = User::factory()->withOrganization()->create();
    Tag::factory()->forOrganization($otherUser)->createdBy($otherUser)->create();

    $this->actingAs($user)
        ->get(route('policies.documents.index', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('tags', 0));
});

test('shares the document upload config for the dropzone', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create();

    $this->actingAs($user)
        ->get(route('policies.documents.index', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('uploadConfig.max_size_bytes', config('documents.max_size'))
            ->where('uploadConfig.max_files_per_batch', config('documents.max_files_per_batch'))
            ->has('uploadConfig.allowed_extensions', count(config('documents.allowed_mimes')))
        );
});
