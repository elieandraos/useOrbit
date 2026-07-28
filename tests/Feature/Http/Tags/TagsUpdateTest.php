<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\Tag;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $tag = Tag::factory()->create();

    $this->patch(route('tags.update', $tag), ['name' => 'Renewal'])
        ->assertRedirect(route('login'));
});

test('rejects a blank name', function () {
    $user = User::factory()->withOrganization()->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create();

    $this->actingAs($user)
        ->patch(route('tags.update', $tag), ['name' => ''])
        ->assertInvalid(['name']);
});

test('rejects a name already used by another tag in the same organization', function () {
    $user = User::factory()->withOrganization()->create();
    Tag::factory()->forOrganization($user)->createdBy($user)->create(['name' => 'Renewal']);
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create(['name' => 'Payment']);

    $this->actingAs($user)
        ->patch(route('tags.update', $tag), ['name' => 'Renewal'])
        ->assertInvalid(['name']);
});

test('allows renaming a tag to the name it already has', function () {
    $user = User::factory()->withOrganization()->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create(['name' => 'Renewal']);

    $this->actingAs($user)
        ->patch(route('tags.update', $tag), ['name' => 'Renewal'])
        ->assertOk();
});

test('a duplicate name in another organization does not block the rename', function () {
    $user = User::factory()->withOrganization()->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create(['name' => 'Payment']);

    $otherOrganization = Organization::factory()->create();
    Tag::factory()->create(['organization_id' => $otherOrganization->id, 'name' => 'Renewal']);

    $this->actingAs($user)
        ->patch(route('tags.update', $tag), ['name' => 'Renewal'])
        ->assertOk();
});

test('the tag\'s creator can rename it', function () {
    $user = User::factory()->withOrganization()->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create(['name' => 'Old name']);

    $this->actingAs($user)
        ->patch(route('tags.update', $tag), ['name' => 'New name'])
        ->assertOk()
        ->assertJson(['name' => 'New name']);

    $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => 'New name']);
});

test('an org owner can rename anyone\'s tag', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->create();
    $tag = Tag::factory()->forOrganization($member)->createdBy($member)->create(['name' => 'Old name']);

    $this->actingAs($owner)
        ->patch(route('tags.update', $tag), ['name' => 'New name'])
        ->assertOk();

    $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => 'New name']);
});

test('a member who neither created the tag nor is owner is forbidden', function () {
    $organization = Organization::factory()->create();
    $member = User::factory()->forOrganization($organization)->create();
    $otherMember = User::factory()->forOrganization($organization)->create();
    $tag = Tag::factory()->forOrganization($otherMember)->createdBy($otherMember)->create(['name' => 'Old name']);

    $this->actingAs($member)
        ->patch(route('tags.update', $tag), ['name' => 'New name'])
        ->assertForbidden();

    $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => 'Old name']);
});

test('a member from another organization gets 404', function () {
    $user = User::factory()->withOrganization()->create();
    $otherOrganization = Organization::factory()->create();
    $tag = Tag::factory()->create(['organization_id' => $otherOrganization->id]);

    $this->actingAs($user)
        ->patch(route('tags.update', $tag), ['name' => 'New name'])
        ->assertNotFound();
});
