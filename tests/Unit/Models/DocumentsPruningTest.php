<?php

declare(strict_types=1);

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

test('deletes stale pending documents and their staging files', function () {
    Storage::fake('local');
    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create([
        'path' => 'documents-staging/'.Str::uuid(),
        'status' => DocumentStatus::Pending,
        'created_at' => now()->subHours(2),
    ]);
    Storage::disk('local')->put($document->path, 'staged contents');

    $this->artisan('model:prune', ['--model' => [Document::class]])->assertSuccessful();

    expect(Document::query()->whereKey($document->id)->exists())->toBeFalse();
    Storage::disk('local')->assertMissing($document->path);
});

test('leaves recent pending documents untouched', function () {
    Storage::fake('local');
    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->create([
        'path' => 'documents-staging/'.Str::uuid(),
        'status' => DocumentStatus::Pending,
        'created_at' => now()->subMinutes(30),
    ]);
    Storage::disk('local')->put($document->path, 'staged contents');

    $this->artisan('model:prune', ['--model' => [Document::class]])->assertSuccessful();

    expect(Document::query()->whereKey($document->id)->exists())->toBeTrue();
    Storage::disk('local')->assertExists($document->path);
});

test('deletes stale processing documents and their staging files', function () {
    Storage::fake('local');
    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->processing()->create([
        'path' => 'documents-staging/'.Str::uuid(),
        'created_at' => now()->subHours(2),
    ]);
    Storage::disk('local')->put($document->path, 'staged contents');

    $this->artisan('model:prune', ['--model' => [Document::class]])->assertSuccessful();

    expect(Document::query()->whereKey($document->id)->exists())->toBeFalse();
    Storage::disk('local')->assertMissing($document->path);
});

test('leaves old completed documents untouched', function () {
    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->completed()->create([
        'created_at' => now()->subHours(2),
    ]);

    $this->artisan('model:prune', ['--model' => [Document::class]])->assertSuccessful();

    expect(Document::query()->whereKey($document->id)->exists())->toBeTrue();
});

test('deletes stale failed documents and their staging files', function () {
    Storage::fake('local');
    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->failed()->create([
        'path' => 'documents-staging/'.Str::uuid(),
        'created_at' => now()->subHours(2),
    ]);
    Storage::disk('local')->put($document->path, 'staged contents');

    $this->artisan('model:prune', ['--model' => [Document::class]])->assertSuccessful();

    expect(Document::query()->whereKey($document->id)->exists())->toBeFalse();
    Storage::disk('local')->assertMissing($document->path);
});

test('leaves recent failed documents untouched', function () {
    Storage::fake('local');
    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->uploadedBy($user)->failed()->create([
        'path' => 'documents-staging/'.Str::uuid(),
        'created_at' => now()->subMinutes(30),
    ]);
    Storage::disk('local')->put($document->path, 'staged contents');

    $this->artisan('model:prune', ['--model' => [Document::class]])->assertSuccessful();

    expect(Document::query()->whereKey($document->id)->exists())->toBeTrue();
    Storage::disk('local')->assertExists($document->path);
});

test('prunable results span organizations, unaffected by the acting user\'s tenant scope', function () {
    $orgAUser = User::factory()->withOrganization()->create();
    $orgBUser = User::factory()->withOrganization()->create();
    $staleInOrgA = Document::factory()->forOrganization($orgAUser)->uploadedBy($orgAUser)->create([
        'created_at' => now()->subHours(2),
    ]);
    $staleInOrgB = Document::factory()->forOrganization($orgBUser)->uploadedBy($orgBUser)->create([
        'created_at' => now()->subHours(2),
    ]);
    $this->actingAs($orgAUser);

    $prunableIds = (new Document)->prunable()->pluck('id');

    expect($prunableIds)->toContain($staleInOrgA->id, $staleInOrgB->id);
});
