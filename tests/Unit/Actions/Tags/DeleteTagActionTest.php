<?php

declare(strict_types=1);

use App\Actions\Tags\DeleteTagAction;
use App\Models\Document;
use App\Models\Tag;
use App\Models\User;

test('deletes the tag row and cascades its pivot rows without touching the tagged documents', function () {
    $user = User::factory()->withOrganization()->create();
    $tag = Tag::factory()->forOrganization($user)->createdBy($user)->create();
    $documents = Document::factory()->forOrganization($user)->uploadedBy($user)->count(2)->create();

    $documents->each(fn (Document $document) => $document->tags()->attach($tag, ['organization_id' => $user->current_organization_id]));

    /** @noinspection PhpUnhandledExceptionInspection */
    app(DeleteTagAction::class)->handle($tag);

    $this->assertModelMissing($tag);
    $this->assertDatabaseCount('taggables', 0);
    $documents->each(fn (Document $document) => $this->assertModelExists($document));
});
