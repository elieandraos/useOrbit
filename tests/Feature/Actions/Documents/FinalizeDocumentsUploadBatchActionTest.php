<?php

declare(strict_types=1);

use App\Actions\Documents\FinalizeDocumentsUploadBatchAction;
use App\Enums\DocumentStatus;
use App\Jobs\StoreDocumentJob;
use App\Models\Client;
use App\Models\Document;
use App\Models\User;
use App\Notifications\DocumentsUploadBatchProcessedNotification;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Testing\Fakes\PendingBatchFake;

/**
 * Runs the pending batch's registered `->finally()` callback(s), simulating what
 * a real queue worker does once every job in the batch has finished — the closure
 * under test never touches the `Batch` argument, so an uninitialized instance stands in.
 *
 * @throws ReflectionException
 */
function runFinallyCallbacks(PendingBatchFake $batch): void
{
    /** @var Batch $fakeBatch */
    $fakeBatch = new ReflectionClass(Batch::class)->newInstanceWithoutConstructor();

    foreach ($batch->finallyCallbacks() as $callback) {
        $callback($fakeBatch);
    }
}

test('dispatches a batch containing a job for each pending document owned by the user', function () {
    Bus::fake();
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $first = Document::factory()->forOrganization($user)->uploadedBy($user)->create(['status' => DocumentStatus::Pending]);
    $second = Document::factory()->forOrganization($user)->uploadedBy($user)->create(['status' => DocumentStatus::Pending]);

    /** @noinspection PhpUnhandledExceptionInspection */
    $rejectedCount = app(FinalizeDocumentsUploadBatchAction::class)->handle($user, [$first->id, $second->id]);

    expect($rejectedCount)->toBe(0);
    Bus::assertBatched(fn (PendingBatchFake $batch): bool => $batch->jobs->count() === 2
        && $batch->hasJobs([
            fn (StoreDocumentJob $job): bool => $job->documentId === $first->id,
            fn (StoreDocumentJob $job): bool => $job->documentId === $second->id,
        ]));
});

test('excludes documents that are not pending', function () {
    Bus::fake();
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    Document::factory()->forOrganization($user)->uploadedBy($user)->create(['status' => DocumentStatus::Pending]);
    $completed = Document::factory()->forOrganization($user)->uploadedBy($user)->completed()->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $rejectedCount = app(FinalizeDocumentsUploadBatchAction::class)->handle($user, [$completed->id]);

    expect($rejectedCount)->toBe(1);
    Bus::assertNothingBatched();
});

test('excludes documents uploaded by another user', function () {
    Bus::fake();
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $otherMember = User::factory()->create(['organization_id' => $user->organization_id]);
    $othersDocument = Document::factory()->forOrganization($user)->uploadedBy($otherMember)->create(['status' => DocumentStatus::Pending]);

    /** @noinspection PhpUnhandledExceptionInspection */
    $rejectedCount = app(FinalizeDocumentsUploadBatchAction::class)->handle($user, [$othersDocument->id]);

    expect($rejectedCount)->toBe(1);
    Bus::assertNothingBatched();
});

test('excludes documents from another organization', function () {
    Bus::fake();
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $otherUser = User::factory()->withOrganization()->create();
    $othersDocument = Document::factory()->forOrganization($otherUser)->uploadedBy($otherUser)->create(['status' => DocumentStatus::Pending]);

    /** @noinspection PhpUnhandledExceptionInspection */
    $rejectedCount = app(FinalizeDocumentsUploadBatchAction::class)->handle($user, [$othersDocument->id]);

    expect($rejectedCount)->toBe(1);
    Bus::assertNothingBatched();
});

test('dispatches nothing when no submitted document matches the filters', function () {
    Bus::fake();
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $completed = Document::factory()->forOrganization($user)->uploadedBy($user)->completed()->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $rejectedCount = app(FinalizeDocumentsUploadBatchAction::class)->handle($user, [$completed->id]);

    expect($rejectedCount)->toBe(1);
    Bus::assertNothingBatched();
});

test('returns a partial rejected count when some submitted ids match and others do not', function () {
    Bus::fake();
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $pending = Document::factory()->forOrganization($user)->uploadedBy($user)->create(['status' => DocumentStatus::Pending]);
    $completed = Document::factory()->forOrganization($user)->uploadedBy($user)->completed()->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $rejectedCount = app(FinalizeDocumentsUploadBatchAction::class)->handle($user, [$pending->id, $completed->id]);

    expect($rejectedCount)->toBe(1);
    Bus::assertBatched(fn (PendingBatchFake $batch): bool => $batch->jobs->count() === 1
        && $batch->hasJobs([fn (StoreDocumentJob $job): bool => $job->documentId === $pending->id]));
});

test('does not count a duplicate submitted id as rejected', function () {
    Bus::fake();
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create(['status' => DocumentStatus::Pending]);

    /** @noinspection PhpUnhandledExceptionInspection */
    $rejectedCount = app(FinalizeDocumentsUploadBatchAction::class)->handle($user, [$document->id, $document->id]);

    expect($rejectedCount)->toBe(0);
});

test(/**
 * @throws ReflectionException
 * @throws Throwable
 */ 'notifies the uploader with the batch outcome once every job completes', function () {
    Bus::fake();
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create([
        'status' => DocumentStatus::Pending,
        'documentable_type' => $client->getMorphClass(),
        'documentable_id' => $client->id,
    ]);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(FinalizeDocumentsUploadBatchAction::class)->handle($user, [$document->id]);

    $document->update(['status' => DocumentStatus::Completed]);
    Notification::fake();

    /** @var PendingBatchFake $batch */
    $batch = Bus::batched(fn (PendingBatchFake $batch): bool => true)->first();

    /** @noinspection PhpUnhandledExceptionInspection */
    runFinallyCallbacks($batch);

    Notification::assertSentTo(
        $user,
        DocumentsUploadBatchProcessedNotification::class,
        fn (DocumentsUploadBatchProcessedNotification $notification): bool => $notification->outcome === ['completed' => 1, 'failed' => 0]
            && $notification->documents->pluck('id')->all() === [$document->id],
    );
});

test('notifies the uploader with a failed count when a file in the batch fails to store', function () {
    Bus::fake();
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create();
    $succeeded = Document::factory()->forOrganization($user)->uploadedBy($user)->create([
        'status' => DocumentStatus::Pending,
        'documentable_type' => $client->getMorphClass(),
        'documentable_id' => $client->id,
    ]);
    $failed = Document::factory()->forOrganization($user)->uploadedBy($user)->create([
        'status' => DocumentStatus::Pending,
        'documentable_type' => $client->getMorphClass(),
        'documentable_id' => $client->id,
    ]);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(FinalizeDocumentsUploadBatchAction::class)->handle($user, [$succeeded->id, $failed->id]);

    $succeeded->update(['status' => DocumentStatus::Completed]);
    $failed->update(['status' => DocumentStatus::Failed, 'error_message' => 'Unable to store the file.']);
    Notification::fake();

    /** @var PendingBatchFake $batch */
    $batch = Bus::batched(fn (PendingBatchFake $batch): bool => true)->first();

    /** @noinspection PhpUnhandledExceptionInspection */
    runFinallyCallbacks($batch);

    Notification::assertSentTo(
        $user,
        DocumentsUploadBatchProcessedNotification::class,
        fn (DocumentsUploadBatchProcessedNotification $notification): bool => $notification->outcome === ['completed' => 1, 'failed' => 1],
    );
});
