<?php

declare(strict_types=1);

use App\Enums\DocumentStatus;
use App\Jobs\StoreDocumentJob;
use App\Models\Document;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

test('moves the staged file to the final destination on success', function () {
    Storage::fake('local');
    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create([
        'mime_type' => 'application/pdf',
        'path' => 'documents-staging/'.Str::uuid(),
    ]);
    Storage::disk('local')->put($document->path, 'staged contents');
    $destination = sprintf('organizations/%d/%s/%d/%d.pdf', $document->organization_id, $document->documentable_type, $document->documentable_id, $document->id);

    new StoreDocumentJob($document)->handle();

    Storage::disk('local')->assertExists($destination);
    Storage::disk('local')->assertMissing($document->path);
});

test('sets status to completed, updates the path, and stamps stored_at on success', function () {
    Storage::fake('local');
    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create([
        'mime_type' => 'application/pdf',
        'path' => 'documents-staging/'.Str::uuid(),
    ]);
    Storage::disk('local')->put($document->path, 'staged contents');
    $destination = sprintf('organizations/%d/%s/%d/%d.pdf', $document->organization_id, $document->documentable_type, $document->documentable_id, $document->id);

    new StoreDocumentJob($document)->handle();

    $fresh = $document->fresh();
    expect($fresh->status)->toBe(DocumentStatus::Completed)
        ->and($fresh->path)->toBe($destination)
        ->and($fresh->stored_at)->not->toBeNull();
});

test('moves the staged file and updates the disk column on the configured non-local disk', function () {
    config(['documents.disk' => 's3']);
    Storage::fake('s3');
    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create([
        'mime_type' => 'application/pdf',
        'disk' => 's3',
        'path' => 'documents-staging/'.Str::uuid(),
    ]);
    Storage::disk('s3')->put($document->path, 'staged contents');
    $destination = sprintf('organizations/%d/%s/%d/%d.pdf', $document->organization_id, $document->documentable_type, $document->documentable_id, $document->id);

    new StoreDocumentJob($document)->handle();

    Storage::disk('s3')->assertExists($destination);
    $fresh = $document->fresh();
    expect($fresh->disk)->toBe('s3')
        ->and($fresh->path)->toBe($destination)
        ->and($fresh->status)->toBe(DocumentStatus::Completed);
});

test('finalizes the row without moving when the destination already exists and passes its checksum', function () {
    Storage::fake('local');
    $user = User::factory()->withOrganization()->create();
    $contents = 'already moved by a prior crashed attempt';
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create([
        'mime_type' => 'application/pdf',
        'path' => 'documents-staging/'.Str::uuid(),
        'checksum' => hash('sha256', $contents),
    ]);
    $destination = sprintf('organizations/%d/%s/%d/%d.pdf', $document->organization_id, $document->documentable_type, $document->documentable_id, $document->id);
    Storage::disk('local')->put($destination, $contents);

    new StoreDocumentJob($document)->handle();

    $fresh = $document->fresh();
    expect($fresh->status)->toBe(DocumentStatus::Completed)
        ->and($fresh->path)->toBe($destination);
    Storage::disk('local')->assertExists($destination);
});

test('re-copies the staged file when the destination copy fails its checksum', function () {
    Storage::fake('local');
    $user = User::factory()->withOrganization()->create();
    $stagedContents = 'staged contents';
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create([
        'mime_type' => 'application/pdf',
        'path' => 'documents-staging/'.Str::uuid(),
        'checksum' => hash('sha256', $stagedContents),
    ]);
    $destination = sprintf('organizations/%d/%s/%d/%d.pdf', $document->organization_id, $document->documentable_type, $document->documentable_id, $document->id);
    Storage::disk('local')->put($document->path, $stagedContents);
    Storage::disk('local')->put($destination, 'a partial write from a crashed attempt');

    new StoreDocumentJob($document)->handle();

    $fresh = $document->fresh();
    expect($fresh->status)->toBe(DocumentStatus::Completed)
        ->and(Storage::disk('local')->get($destination))->toBe($stagedContents);
});

test('throws when the destination fails its checksum and the staged file is gone', function () {
    Storage::fake('local');
    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create([
        'mime_type' => 'application/pdf',
        'path' => 'documents-staging/'.Str::uuid(),
        'checksum' => hash('sha256', 'staged contents'),
    ]);
    $destination = sprintf('organizations/%d/%s/%d/%d.pdf', $document->organization_id, $document->documentable_type, $document->documentable_id, $document->id);
    Storage::disk('local')->put($destination, 'a partial write from a crashed attempt');
    // No staged file at $document->path: nothing left to recover from.

    new StoreDocumentJob($document)->handle();
})->throws(RuntimeException::class, 'staged file is missing and the destination copy failed its integrity check.');

test('is a no-op when the document is no longer pending', function () {
    Storage::fake('local');
    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->completed()->create();
    $originalPath = $document->path;

    new StoreDocumentJob($document)->handle();

    $fresh = $document->fresh();
    expect($fresh->status)->toBe(DocumentStatus::Completed)
        ->and($fresh->path)->toBe($originalPath);
});

test('is a no-op when the document is already being processed by another worker', function () {
    Storage::fake('local');
    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->processing()->create();
    $originalPath = $document->path;

    new StoreDocumentJob($document)->handle();

    $fresh = $document->fresh();
    expect($fresh->status)->toBe(DocumentStatus::Processing)
        ->and($fresh->path)->toBe($originalPath);
});

test('claiming transitions the document to processing and blocks a second concurrent claim', function () {
    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create();
    $job = new StoreDocumentJob($document);
    $claim = fn (): ?Document => Closure::bind(fn (): ?Document => $this->claim(), $job, $job)();

    $claimed = $claim();

    expect($claimed)->not->toBeNull()
        ->and($claimed->status)->toBe(DocumentStatus::Processing)
        ->and($document->fresh()->status)->toBe(DocumentStatus::Processing);

    expect($claim())->toBeNull();
});

test('reverts the document to pending when the attempt throws, so it can be retried', function () {
    Storage::fake('local');
    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create([
        'mime_type' => 'application/zip',
        'path' => 'documents-staging/'.Str::uuid(),
    ]);
    Storage::disk('local')->put($document->path, 'staged contents');

    expect(fn () => new StoreDocumentJob($document)->handle())->toThrow(RuntimeException::class);

    expect($document->fresh()->status)->toBe(DocumentStatus::Pending);
});

test('failed hook marks the document as failed with the exception message', function () {
    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create();

    new StoreDocumentJob($document)->failed(new RuntimeException('Disk write failed.'));

    $fresh = $document->fresh();
    expect($fresh->status)->toBe(DocumentStatus::Failed)
        ->and($fresh->error_message)->toBe('Disk write failed.');
});

test('is a no-op when the batch has been cancelled', function () {
    Storage::fake('local');
    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create([
        'mime_type' => 'application/pdf',
        'path' => 'documents-staging/'.Str::uuid(),
    ]);
    Storage::disk('local')->put($document->path, 'staged contents');

    $job = new StoreDocumentJob($document);
    /** @var StoreDocumentJob $job */
    [$job] = $job->withFakeBatch(cancelledAt: CarbonImmutable::now());

    $job->handle();

    $fresh = $document->fresh();
    expect($fresh->status)->toBe(DocumentStatus::Pending)
        ->and($fresh->path)->toBe($document->path);
    Storage::disk('local')->assertExists($document->path);
});

test('throws when the mime type has no allowed extension', function () {
    Storage::fake('local');
    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create([
        'mime_type' => 'application/zip',
        'path' => 'documents-staging/'.Str::uuid(),
    ]);
    Storage::disk('local')->put($document->path, 'staged contents');

    new StoreDocumentJob($document)->handle();
})->throws(RuntimeException::class, 'has no validated extension for mime type [application/zip].');

test('retries up to three times with an exponential backoff schedule', function () {
    $job = new StoreDocumentJob(Document::factory()->make());

    expect($job->tries)->toBe(3)
        ->and($job->backoff())->toBe([10, 30, 60]);
});
