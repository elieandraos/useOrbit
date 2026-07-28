<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\Document;
use App\Models\Organization;
use App\Models\Tag;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $tag = Tag::factory()->create();

    $this->delete(route('tags.destroy', $tag))
        ->assertRedirect(route('login'));
});

test('the tag\'s creator can delete it', function () {
    $user = User::factory()->withOrganization()->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create();

    $this->actingAs($user)
        ->delete(route('tags.destroy', $tag))
        ->assertRedirectBack()
        ->assertHasInertiaFlash('success', 'Tag deleted.');

    $this->assertModelMissing($tag);
});

test('an org owner can delete anyone\'s tag', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->create();
    $tag = Tag::factory()->forOrganization($member)->createdBy($member)->create();

    $this->actingAs($owner)
        ->delete(route('tags.destroy', $tag))
        ->assertRedirectBack()
        ->assertHasInertiaFlash('success', 'Tag deleted.');

    $this->assertModelMissing($tag);
});

test('a member who neither created the tag nor is owner is forbidden', function () {
    $organization = Organization::factory()->create();
    $member = User::factory()->forOrganization($organization)->create();
    $otherMember = User::factory()->forOrganization($organization)->create();
    $tag = Tag::factory()->forOrganization($otherMember)->createdBy($otherMember)->create();

    $this->actingAs($member)
        ->delete(route('tags.destroy', $tag))
        ->assertForbidden();

    $this->assertModelExists($tag);
});

test('a member from another organization gets 404', function () {
    $user = User::factory()->withOrganization()->create();
    $otherOrganization = Organization::factory()->create();
    $tag = Tag::factory()->create(['organization_id' => $otherOrganization->id]);

    $this->actingAs($user)
        ->delete(route('tags.destroy', $tag))
        ->assertNotFound();
});

test('deleting a tag attached to multiple documents removes all its pivot rows and leaves the documents intact', function () {
    $user = User::factory()->withOrganization()->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create();
    $documents = Document::factory()->forOrganization($user)->uploadedBy($user)->count(2)->create();

    $documents->each(fn (Document $document) => $document->tags()->attach($tag, ['organization_id' => $user->current_organization_id]));

    $this->actingAs($user)
        ->delete(route('tags.destroy', $tag))
        ->assertRedirectBack();

    $this->assertModelMissing($tag);
    $this->assertDatabaseCount('taggables', 0);
    $documents->each(fn (Document $document) => $this->assertModelExists($document));
});
