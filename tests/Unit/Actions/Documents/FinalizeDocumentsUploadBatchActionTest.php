<?php

declare(strict_types=1);

use App\Actions\Documents\FinalizeDocumentsUploadBatchAction;
use App\Enums\DocumentStatus;
use App\Jobs\StoreDocumentJob;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Testing\Fakes\PendingBatchFake;

test('dispatches a batch containing a job for each pending document owned by the user', function () {
    Bus::fake();
    $user = User::factory()->withOrganization()->create();
    $first = Document::factory()->forOrganization($user)->uploadedBy($user)->create(['status' => DocumentStatus::Pending]);
    $second = Document::factory()->forOrganization($user)->uploadedBy($user)->create(['status' => DocumentStatus::Pending]);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(FinalizeDocumentsUploadBatchAction::class)->handle($user, [$first->id, $second->id]);

    /** @noinspection PhpParamsInspection */
    Bus::assertBatched(fn (PendingBatchFake $batch): bool => $batch->jobs->count() === 2
        && $batch->hasJobs([
            fn (StoreDocumentJob $job): bool => $job->document->is($first),
            fn (StoreDocumentJob $job): bool => $job->document->is($second),
        ]));
});

test('excludes documents that are not pending', function () {
    Bus::fake();
    $user = User::factory()->withOrganization()->create();
    Document::factory()->forOrganization($user)->uploadedBy($user)->create(['status' => DocumentStatus::Pending]);
    $completed = Document::factory()->forOrganization($user)->uploadedBy($user)->completed()->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    app(FinalizeDocumentsUploadBatchAction::class)->handle($user, [$completed->id]);

    Bus::assertNothingBatched();
});

test('excludes documents uploaded by another user', function () {
    Bus::fake();
    $user = User::factory()->withOrganization()->create();
    $otherMember = User::factory()->create(['current_organization_id' => $user->current_organization_id]);
    $othersDocument = Document::factory()->forOrganization($user)->uploadedBy($otherMember)->create(['status' => DocumentStatus::Pending]);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(FinalizeDocumentsUploadBatchAction::class)->handle($user, [$othersDocument->id]);

    Bus::assertNothingBatched();
});

test('excludes documents from another organization', function () {
    Bus::fake();
    $user = User::factory()->withOrganization()->create();
    $otherUser = User::factory()->withOrganization()->create();
    $othersDocument = Document::factory()->forOrganization($otherUser)->uploadedBy($otherUser)->create(['status' => DocumentStatus::Pending]);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(FinalizeDocumentsUploadBatchAction::class)->handle($user, [$othersDocument->id]);

    Bus::assertNothingBatched();
});

test('dispatches nothing when no submitted document matches the filters', function () {
    Bus::fake();
    $user = User::factory()->withOrganization()->create();
    $completed = Document::factory()->forOrganization($user)->uploadedBy($user)->completed()->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    app(FinalizeDocumentsUploadBatchAction::class)->handle($user, [$completed->id]);

    Bus::assertNothingBatched();
});
