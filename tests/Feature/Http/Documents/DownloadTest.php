<?php

declare(strict_types=1);

use App\Models\Document;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

test('guests are redirected to the login page', function () {
    $document = Document::factory()->completed()->create();

    $this->get(route('documents.download', $document))
        ->assertRedirect(route('login'));
});

test('pending document cannot be downloaded', function () {
    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->create();

    $this->actingAs($user)
        ->get(route('documents.download', $document))
        ->assertNotFound();
});

test('failed document cannot be downloaded', function () {
    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->failed()->create();

    $this->actingAs($user)
        ->get(route('documents.download', $document))
        ->assertNotFound();
});

test('user gets 404 for a completed document from another organization', function () {
    $user = User::factory()->withOrganization()->create();

    $otherOrganization = Organization::factory()->create();
    $document = Document::factory()->completed()->create(['organization_id' => $otherOrganization->id]);

    $this->actingAs($user)
        ->get(route('documents.download', $document))
        ->assertNotFound();
});

test('completed document on the local disk is streamed with the original filename', function () {
    Storage::fake('local');

    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->completed()->create(['disk' => 'local']);

    Storage::disk('local')->put($document->path, 'file contents');

    $this->actingAs($user)
        ->get(route('documents.download', $document))
        ->assertDownload($document->original_filename);
});

test('completed document on the s3 disk redirects to a temporary signed url', function () {
    Storage::fake('s3');
    Storage::disk('s3')->buildTemporaryUrlsUsing(
        fn (string $path, DateTimeInterface $expiration, array $options): string => 'https://s3.fake/'.$path
    );

    $user = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($user)->completed()->create(['disk' => 's3']);

    $this->actingAs($user)
        ->get(route('documents.download', $document))
        ->assertRedirect('https://s3.fake/'.$document->path);
});
