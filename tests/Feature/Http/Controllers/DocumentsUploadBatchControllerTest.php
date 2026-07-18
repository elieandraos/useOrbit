<?php

declare(strict_types=1);

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\User;
use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Facades\Bus;

test('guests are redirected to the login page', function () {
    $this->post(route('documents.batch'), ['document_ids' => [1]])
        ->assertRedirect(route('login'));
});

test('rejects a document id list exceeding the configured max files per batch', function () {
    $user = User::factory()->withOrganization()->create();
    $ids = range(1, config('documents.max_files_per_batch') + 1);

    $this->actingAs($user)
        ->post(route('documents.batch'), ['document_ids' => $ids])
        ->assertInvalid(['document_ids']);
});

test('dispatches a batch for the submitted pending documents', function () {
    Bus::fake();
    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create(['status' => DocumentStatus::Pending]);

    $this->actingAs($user)
        ->post(route('documents.batch'), ['document_ids' => [$document->id]])
        ->assertRedirectBack();

    /** @noinspection PhpParamsInspection */
    Bus::assertBatched(fn (PendingBatch $batch): bool => $batch->jobs->count() === 1);
});
