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

test('finalizes the row without moving when the destination already exists', function () {
    Storage::fake('local');
    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create([
        'mime_type' => 'application/pdf',
        'path' => 'documents-staging/'.Str::uuid(),
    ]);
    $destination = sprintf('organizations/%d/%s/%d/%d.pdf', $document->organization_id, $document->documentable_type, $document->documentable_id, $document->id);
    Storage::disk('local')->put($destination, 'already moved by a prior crashed attempt');

    new StoreDocumentJob($document)->handle();

    $fresh = $document->fresh();
    expect($fresh->status)->toBe(DocumentStatus::Completed)
        ->and($fresh->path)->toBe($destination);
});

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
