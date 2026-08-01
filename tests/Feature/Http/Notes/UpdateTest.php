<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\Note;
use App\Models\Organization;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $note = Note::factory()->create();

    $this->patch(route('notes.update', $note), ['body' => 'Updated.'])
        ->assertRedirect(route('login'));
});

test('author can update their own note', function () {
    $user = User::factory()->withOrganization()->create();
    $note = Note::factory()->forOrganization($user)->createdBy($user)->create(['body' => 'Original.']);

    $this->actingAs($user)
        ->patch(route('notes.update', $note), ['body' => 'Updated.', 'pinned' => true])
        ->assertOk()
        ->assertJson(['body' => 'Updated.', 'pinned' => true]);

    $this->assertDatabaseHas('notes', ['id' => $note->id, 'body' => 'Updated.', 'pinned' => true]);
});

test('org owner can update another member note', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->create();
    $note = Note::factory()->forOrganization($member)->createdBy($member)->create(['body' => 'Original.']);

    $this->actingAs($owner)
        ->patch(route('notes.update', $note), ['body' => 'Updated by owner.'])
        ->assertOk();

    $this->assertDatabaseHas('notes', ['id' => $note->id, 'body' => 'Updated by owner.']);
});

test('a non-owner non-author member is forbidden from updating the note', function () {
    $organization = Organization::factory()->create();
    $member = User::factory()->forOrganization($organization)->create();
    $otherMember = User::factory()->forOrganization($organization)->create();
    $note = Note::factory()->forOrganization($otherMember)->createdBy($otherMember)->create(['body' => 'Original.']);

    $this->actingAs($member)
        ->patch(route('notes.update', $note), ['body' => 'Updated.'])
        ->assertForbidden();

    $this->assertDatabaseHas('notes', ['id' => $note->id, 'body' => 'Original.']);
});

test('a note from another organization is not found', function () {
    $user = User::factory()->withOrganization()->create();
    $otherOrganization = Organization::factory()->create();
    $note = Note::factory()->create(['organization_id' => $otherOrganization->id]);

    $this->actingAs($user)
        ->patch(route('notes.update', $note), ['body' => 'Updated.'])
        ->assertNotFound();
});

test('rejects a body exceeding the configured max length', function () {
    $user = User::factory()->withOrganization()->create();
    $note = Note::factory()->forOrganization($user)->createdBy($user)->create();
    $body = str_repeat('a', config('notes.max_length') + 1);

    $this->actingAs($user)
        ->patch(route('notes.update', $note), ['body' => $body])
        ->assertInvalid(['body']);
});

test('accepts a body exactly at the configured max length', function () {
    $user = User::factory()->withOrganization()->create();
    $note = Note::factory()->forOrganization($user)->createdBy($user)->create();
    $body = str_repeat('a', config('notes.max_length'));

    $this->actingAs($user)
        ->patch(route('notes.update', $note), ['body' => $body])
        ->assertOk();

    $this->assertDatabaseHas('notes', ['id' => $note->id, 'body' => $body]);
});

test('preserves line breaks in the body', function () {
    $user = User::factory()->withOrganization()->create();
    $note = Note::factory()->forOrganization($user)->createdBy($user)->create();
    $body = "Line one.\nLine two.\nLine three.";

    $this->actingAs($user)
        ->patch(route('notes.update', $note), ['body' => $body])
        ->assertOk()
        ->assertJson(['body' => $body]);

    $this->assertDatabaseHas('notes', ['id' => $note->id, 'body' => $body]);
});
