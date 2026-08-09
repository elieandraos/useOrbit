<?php

declare(strict_types=1);

use App\Models\Document;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $document = Document::factory()->completed()->create();

    $this->delete(route('documents.destroy', $document))
        ->assertRedirect(route('login'));
});

test('member can delete their own completed document', function () {
    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->completed()->create();

    $this->actingAs($user)
        ->delete(route('documents.destroy', $document))
        ->assertRedirectBack()
        ->assertHasInertiaFlash('success', 'Document deleted.');
});

test('member who did not upload the document is forbidden from deleting it', function () {
    $user = User::factory()->withOrganization()->create();
    $otherMember = User::factory()->create(['organization_id' => $user->organization_id]);
    $document = Document::factory()->forOrganization($user)->uploadedBy($otherMember)->completed()->create();

    $this->actingAs($user)
        ->delete(route('documents.destroy', $document))
        ->assertForbidden();
});
