<?php

declare(strict_types=1);

use App\Actions\Tags\AttachTagAction;
use App\Models\Document;
use App\Models\Organization;
use App\Models\Tag;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

test('attaches the tag to the document via the pivot row', function () {
    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    app(AttachTagAction::class)->handle($document, $tag);

    $this->assertDatabaseHas('document_tag', [
        'tag_id' => $tag->id,
        'document_id' => $document->id,
    ]);
});

test('attaching the same tag twice is idempotent', function () {
    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    app(AttachTagAction::class)->handle($document, $tag);
    /** @noinspection PhpUnhandledExceptionInspection */
    app(AttachTagAction::class)->handle($document, $tag);

    $this->assertDatabaseCount('document_tag', 1);
});

test('attaching a tag from another organization to the document is rejected', function () {
    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create();
    $otherOrganization = Organization::factory()->create();
    $tag = Tag::factory()->create(['organization_id' => $otherOrganization->id]);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(AttachTagAction::class)->handle($document, $tag);
})->throws(NotFoundHttpException::class);
