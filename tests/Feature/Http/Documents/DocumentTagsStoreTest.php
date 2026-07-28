<?php

declare(strict_types=1);

use App\Models\Document;
use App\Models\Organization;
use App\Models\Tag;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $document = Document::factory()->create();
    $tag = Tag::factory()->create(['organization_id' => $document->organization_id]);

    $this->post(route('documents.tags.store', [$document, $tag]))
        ->assertRedirect(route('login'));
});

test('a member who can view the document can attach a tag', function () {
    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create();

    $this->actingAs($user)
        ->post(route('documents.tags.store', [$document, $tag]))
        ->assertOk();

    $this->assertDatabaseHas('taggables', [
        'tag_id' => $tag->id,
        'taggable_type' => $document->getMorphClass(),
        'taggable_id' => $document->id,
        'organization_id' => $user->current_organization_id,
    ]);
});

test('a member from another organization gets 404 when the document belongs to another org', function () {
    $user = User::factory()->withOrganization()->create();
    $otherOrganization = Organization::factory()->create();
    $document = Document::factory()->completed()->create(['organization_id' => $otherOrganization->id]);
    $tag = Tag::factory()->create(['organization_id' => $otherOrganization->id]);

    $this->actingAs($user)
        ->post(route('documents.tags.store', [$document, $tag]))
        ->assertNotFound();
});

test('a member from another organization gets 404 when the tag belongs to another org', function () {
    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create();
    $otherOrganization = Organization::factory()->create();
    $tag = Tag::factory()->create(['organization_id' => $otherOrganization->id]);

    $this->actingAs($user)
        ->post(route('documents.tags.store', [$document, $tag]))
        ->assertNotFound();
});

test('attaching the same tag twice does not error or duplicate the pivot row', function () {
    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create();

    $this->actingAs($user)->post(route('documents.tags.store', [$document, $tag]))->assertOk();
    $this->actingAs($user)->post(route('documents.tags.store', [$document, $tag]))->assertOk();

    $this->assertDatabaseCount('taggables', 1);
});

test('response includes the updated usage_count', function () {
    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create();
    $otherDocument = Document::factory()->forOrganization($user)->uploadedBy($user)->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create();

    $otherDocument->tags()->attach($tag, ['organization_id' => $user->current_organization_id]);

    $response = $this->actingAs($user)
        ->post(route('documents.tags.store', [$document, $tag]))
        ->assertOk();

    expect($response->json('0.usage_count'))->toBe(2);
});

test('usage_count ignores attachments belonging to a different owner type', function () {
    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create();
    $policyDocument = Document::factory()->forOrganization($user)->uploadedBy($user)->create(['documentable_type' => 'policies']);
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create();

    $policyDocument->tags()->attach($tag, ['organization_id' => $user->current_organization_id]);

    $response = $this->actingAs($user)
        ->post(route('documents.tags.store', [$document, $tag]))
        ->assertOk();

    expect($response->json('0.usage_count'))->toBe(1);
});
