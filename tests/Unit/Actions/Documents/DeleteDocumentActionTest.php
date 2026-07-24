<?php

declare(strict_types=1);

use App\Actions\Documents\DeleteDocumentAction;
use App\Models\Document;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
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

test('logs a warning when the file fails to delete from disk after the row is removed', function () {
    Log::spy();
    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->completed()->create(['disk' => 'local']);

    $failingDisk = Mockery::mock(Filesystem::class);
    $failingDisk->shouldReceive('delete')->once()->with($document->path)->andReturn(false);
    Storage::set('local', $failingDisk);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(DeleteDocumentAction::class)->handle($document);

    $this->assertModelMissing($document);
    Log::shouldHaveReceived('warning')->once()->with(
        'Failed to delete document file after removing its database row.',
        ['document_id' => $document->id, 'disk' => 'local', 'path' => $document->path],
    );
});
