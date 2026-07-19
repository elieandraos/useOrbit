<?php

declare(strict_types=1);

use App\Actions\Documents\UploadDocumentAction;
use App\Enums\DocumentStatus;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('stashes the file to the local staging disk', function () {
    Storage::fake('local');
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();
    $file = UploadedFile::fake()->create('report.pdf', 100, 'application/pdf');

    /** @noinspection PhpUnhandledExceptionInspection */
    $document = app(UploadDocumentAction::class)->handle($user, $client, $file);

    Storage::disk('local')->assertExists($document->path);
    expect($document->path)->toStartWith('documents-staging/');
});

test('creates a pending document row', function () {
    Storage::fake('local');
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();
    $file = UploadedFile::fake()->create('report.pdf', 100, 'application/pdf');

    /** @noinspection PhpUnhandledExceptionInspection */
    $document = app(UploadDocumentAction::class)->handle($user, $client, $file);

    expect($document->status)->toBe(DocumentStatus::Pending)
        ->and($document->stored_at)->toBeNull();
});

test('scopes the document to the user current organization', function () {
    Storage::fake('local');
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();
    $file = UploadedFile::fake()->create('report.pdf', 100, 'application/pdf');

    /** @noinspection PhpUnhandledExceptionInspection */
    $document = app(UploadDocumentAction::class)->handle($user, $client, $file);

    expect($document->organization_id)->toBe($user->current_organization_id);
});

test('sets uploaded_by to the user id', function () {
    Storage::fake('local');
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();
    $file = UploadedFile::fake()->create('report.pdf', 100, 'application/pdf');

    /** @noinspection PhpUnhandledExceptionInspection */
    $document = app(UploadDocumentAction::class)->handle($user, $client, $file);

    expect($document->uploaded_by)->toBe($user->id);
});

test('associates the document with the given documentable', function () {
    Storage::fake('local');
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();
    $file = UploadedFile::fake()->create('report.pdf', 100, 'application/pdf');

    /** @noinspection PhpUnhandledExceptionInspection */
    $document = app(UploadDocumentAction::class)->handle($user, $client, $file);

    expect($document->documentable_type)->toBe($client->getMorphClass())
        ->and($document->documentable_id)->toBe($client->id);
});

test('keeps the original filename separate from the staging path', function () {
    Storage::fake('local');
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();
    $file = UploadedFile::fake()->create('report.pdf', 100, 'application/pdf');

    /** @noinspection PhpUnhandledExceptionInspection */
    $document = app(UploadDocumentAction::class)->handle($user, $client, $file);

    expect($document->original_filename)->toBe('report.pdf')
        ->and($document->path)->not->toContain('report.pdf');
});

test('records the mime type and size', function () {
    Storage::fake('local');
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();
    $file = UploadedFile::fake()->create('report.pdf', 100, 'application/pdf');

    /** @noinspection PhpUnhandledExceptionInspection */
    $document = app(UploadDocumentAction::class)->handle($user, $client, $file);

    expect($document->mime_type)->toBe('application/pdf')
        ->and($document->size_in_bytes)->toBe($file->getSize());
});
