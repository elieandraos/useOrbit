<?php

declare(strict_types=1);

use App\Actions\Tags\DetachTagAction;
use App\Models\Document;
use App\Models\Tag;
use App\Models\User;

test('removes the pivot row without touching the tag or the taggable', function () {
    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create();
    $document->tags()->attach($tag, ['organization_id' => $user->current_organization_id]);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(DetachTagAction::class)->handle($document, $tag);

    $this->assertDatabaseMissing('taggables', [
        'tag_id' => $tag->id,
        'taggable_type' => $document->getMorphClass(),
        'taggable_id' => $document->id,
    ]);
    $this->assertDatabaseHas('tags', ['id' => $tag->id]);
    $this->assertDatabaseHas('documents', ['id' => $document->id]);
});

test('detaching a tag that was never attached is a no-op', function () {
    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    app(DetachTagAction::class)->handle($document, $tag);

    $this->assertDatabaseCount('taggables', 0);
});
