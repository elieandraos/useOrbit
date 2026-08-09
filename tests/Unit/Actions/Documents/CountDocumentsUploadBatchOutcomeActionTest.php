<?php

declare(strict_types=1);

use App\Actions\Documents\CountDocumentsUploadBatchOutcomeAction;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Collection;

test('counts completed and failed documents among the given ids', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $completed = Document::factory(2)->forOrganization($user)->uploadedBy($user)->completed()->create();
    $failed = Document::factory()->forOrganization($user)->uploadedBy($user)->failed()->create();

    /** @var Collection<int, int> $ids */
    $ids = $completed->pluck('id')->push($failed->id);

    /** @noinspection PhpUnhandledExceptionInspection */
    $outcome = app(CountDocumentsUploadBatchOutcomeAction::class)->handle($ids);

    expect($outcome)->toBe(['completed' => 2, 'failed' => 1]);
});

test('ignores documents still pending', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $pending = Document::factory()->forOrganization($user)->uploadedBy($user)->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $outcome = app(CountDocumentsUploadBatchOutcomeAction::class)->handle(collect([$pending->id]));

    expect($outcome)->toBe(['completed' => 0, 'failed' => 0]);
});

test('ignores documents outside the given ids', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    Document::factory()->forOrganization($user)->uploadedBy($user)->completed()->create();
    $failed = Document::factory()->forOrganization($user)->uploadedBy($user)->failed()->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $outcome = app(CountDocumentsUploadBatchOutcomeAction::class)->handle(collect([$failed->id]));

    expect($outcome)->toBe(['completed' => 0, 'failed' => 1]);
});
