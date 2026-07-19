<?php

declare(strict_types=1);

use App\Actions\Documents\DeleteDocumentAction;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

test('deletes the document row', function () {
    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->completed()->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    app(DeleteDocumentAction::class)->handle($document);

    $this->assertModelMissing($document);
});

test('deletes the file from disk', function () {
    Storage::fake('local');
    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->completed()->create(['disk' => 'local']);
    Storage::disk('local')->put($document->path, 'contents');

    /** @noinspection PhpUnhandledExceptionInspection */
    app(DeleteDocumentAction::class)->handle($document);

    Storage::disk('local')->assertMissing($document->path);
});
