<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\Note;
use App\Models\Organization;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $note = Note::factory()->create();

    $this->delete(route('notes.destroy', $note))
        ->assertRedirect(route('login'));
});

test('author can delete their own note', function () {
    $user = User::factory()->withOrganization()->create();
    $note = Note::factory()->forOrganization($user)->createdBy($user)->create();

    $this->actingAs($user)
        ->delete(route('notes.destroy', $note))
        ->assertRedirectBack()
        ->assertHasInertiaFlash('success', 'Note deleted.');

    $this->assertModelMissing($note);
});

test('org owner can delete another member note', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->create();
    $note = Note::factory()->forOrganization($member)->createdBy($member)->create();

    $this->actingAs($owner)
        ->delete(route('notes.destroy', $note))
        ->assertRedirectBack()
        ->assertHasInertiaFlash('success', 'Note deleted.');

    $this->assertModelMissing($note);
});

test('a non-owner non-author member is forbidden from deleting the note', function () {
    $organization = Organization::factory()->create();
    $member = User::factory()->forOrganization($organization)->create();
    $otherMember = User::factory()->forOrganization($organization)->create();
    $note = Note::factory()->forOrganization($otherMember)->createdBy($otherMember)->create();

    $this->actingAs($member)
        ->delete(route('notes.destroy', $note))
        ->assertForbidden();

    $this->assertModelExists($note);
});

test('a note from another organization is not found', function () {
    $user = User::factory()->withOrganization()->create();
    $otherOrganization = Organization::factory()->create();
    $note = Note::factory()->create(['organization_id' => $otherOrganization->id]);

    $this->actingAs($user)
        ->delete(route('notes.destroy', $note))
        ->assertNotFound();
});
