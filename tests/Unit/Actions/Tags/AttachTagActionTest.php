<?php

declare(strict_types=1);

use App\Actions\Tags\AttachTagAction;
use App\Models\Document;
use App\Models\Tag;
use App\Models\User;

test('attaches the tag with the tag organization_id on the pivot row', function () {
    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    app(AttachTagAction::class)->handle($document, $tag);

    $this->assertDatabaseHas('taggables', [
        'tag_id' => $tag->id,
        'taggable_type' => $document->getMorphClass(),
        'taggable_id' => $document->id,
        'organization_id' => $tag->organization_id,
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

    $this->assertDatabaseCount('taggables', 1);
});
