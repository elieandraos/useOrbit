<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Document;
use App\Models\Organization;
use App\Models\Tag;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $document = Document::factory()->create();
    $tag = Tag::factory()->create(['organization_id' => $document->organization_id]);

    $this->delete(route('documents.tags.destroy', [$document, $tag]))
        ->assertRedirect(route('login'));
});

test('a member who can view the document can detach a tag', function () {
    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create();
    $document->tags()->attach($tag);

    $this->actingAs($user)
        ->delete(route('documents.tags.destroy', [$document, $tag]))
        ->assertOk();

    $this->assertDatabaseMissing('document_tag', [
        'tag_id' => $tag->id,
        'document_id' => $document->id,
    ]);
});

test('a member from another organization gets 404 when the document belongs to another org', function () {
    $user = User::factory()->withOrganization()->create();
    $otherOrganization = Organization::factory()->create();
    $document = Document::factory()->completed()->create(['organization_id' => $otherOrganization->id]);
    $tag = Tag::factory()->create(['organization_id' => $otherOrganization->id]);

    $this->actingAs($user)
        ->delete(route('documents.tags.destroy', [$document, $tag]))
        ->assertNotFound();
});

test('a member from another organization gets 404 when the tag belongs to another org', function () {
    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create();
    $otherOrganization = Organization::factory()->create();
    $tag = Tag::factory()->create(['organization_id' => $otherOrganization->id]);

    $this->actingAs($user)
        ->delete(route('documents.tags.destroy', [$document, $tag]))
        ->assertNotFound();
});

test('detaching a tag that was never attached is a no-op', function () {
    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create();

    $this->actingAs($user)
        ->delete(route('documents.tags.destroy', [$document, $tag]))
        ->assertOk();

    $this->assertDatabaseCount('document_tag', 0);
});

test('response reflects the updated tag list and counts', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create([
        'documentable_type' => $client->getMorphClass(),
        'documentable_id' => $client->id,
    ]);
    $otherDocument = Document::factory()->forOrganization($user)->uploadedBy($user)->create([
        'documentable_type' => $client->getMorphClass(),
        'documentable_id' => $client->id,
    ]);
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create();

    $document->tags()->attach($tag);
    $otherDocument->tags()->attach($tag);

    $response = $this->actingAs($user)
        ->delete(route('documents.tags.destroy', [$document, $tag]))
        ->assertOk();

    expect($response->json())->toHaveCount(0);

    $counted = Tag::query()->withCount('documents')->findOrFail($tag->id);
    expect($counted->documents_count)->toBe(1);
});

test('usage_count ignores attachments belonging to a different owner type', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create([
        'documentable_type' => $client->getMorphClass(),
        'documentable_id' => $client->id,
    ]);
    $otherDocument = Document::factory()->forOrganization($user)->uploadedBy($user)->create([
        'documentable_type' => $client->getMorphClass(),
        'documentable_id' => $client->id,
    ]);
    $policyDocument = Document::factory()->forOrganization($user)->uploadedBy($user)->create(['documentable_type' => 'policies']);
    $tagToDetach = Tag::factory()->forOrganization($user)->createdBy($user)->create();
    $tagToKeep = Tag::factory()->forOrganization($user)->createdBy($user)->create();

    $document->tags()->attach($tagToDetach);
    $document->tags()->attach($tagToKeep);
    $otherDocument->tags()->attach($tagToKeep);
    $policyDocument->tags()->attach($tagToKeep);

    $response = $this->actingAs($user)
        ->delete(route('documents.tags.destroy', [$document, $tagToDetach]))
        ->assertOk();

    expect($response->json())->toHaveCount(1)
        ->and($response->json('0.usage_count'))->toBe(2);
});

test('usage_count ignores attachments belonging to a different client', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();
    $otherClient = Client::factory()->forOrganization($user)->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create([
        'documentable_type' => $client->getMorphClass(),
        'documentable_id' => $client->id,
    ]);
    $otherClientDocument = Document::factory()->forOrganization($user)->uploadedBy($user)->create([
        'documentable_type' => $otherClient->getMorphClass(),
        'documentable_id' => $otherClient->id,
    ]);
    $tagToDetach = Tag::factory()->forOrganization($user)->createdBy($user)->create();
    $tagToKeep = Tag::factory()->forOrganization($user)->createdBy($user)->create();

    $document->tags()->attach($tagToDetach);
    $document->tags()->attach($tagToKeep);
    $otherClientDocument->tags()->attach($tagToKeep);

    $response = $this->actingAs($user)
        ->delete(route('documents.tags.destroy', [$document, $tagToDetach]))
        ->assertOk();

    expect($response->json())->toHaveCount(1)
        ->and($response->json('0.usage_count'))->toBe(1);
});
