<?php

declare(strict_types=1);

use App\Actions\Documents\UploadDocumentAction;
use App\Enums\DocumentStatus;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\QueryException;
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

test('stages the file to the configured documents disk', function () {
    config(['documents.disk' => 's3']);
    Storage::fake('s3');
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();
    $file = UploadedFile::fake()->create('report.pdf', 100, 'application/pdf');

    /** @noinspection PhpUnhandledExceptionInspection */
    $document = app(UploadDocumentAction::class)->handle($user, $client, $file);

    Storage::disk('s3')->assertExists($document->path);
    expect($document->disk)->toBe('s3');
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

    expect($document->organization_id)->toBe($user->organization_id);
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

test('computes a sha256 checksum of the uploaded file', function () {
    Storage::fake('local');
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();
    $file = UploadedFile::fake()->create('report.pdf', 100, 'application/pdf');
    $expectedChecksum = hash_file('sha256', $file->getRealPath());

    /** @noinspection PhpUnhandledExceptionInspection */
    $document = app(UploadDocumentAction::class)->handle($user, $client, $file);

    expect($document->checksum)->toBe($expectedChecksum);
});

test('deletes the staged file when the document row fails to persist', function () {
    Storage::fake('local');
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->make();
    $file = UploadedFile::fake()->create('report.pdf', 100, 'application/pdf');

    expect(fn () => app(UploadDocumentAction::class)->handle($user, $client, $file))
        ->toThrow(QueryException::class);

    Storage::disk('local')->assertDirectoryEmpty('documents-staging');
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
