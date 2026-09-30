<?php

declare(strict_types=1);

use App\Enums\DocumentStatus;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('guests are redirected to the login page', function () {
    $policy = Policy::factory()->create();

    $this->post(route('policies.documents.store', $policy))
        ->assertRedirect(route('login'));
});

test('rejects a file exceeding the configured max size', function () {
    Storage::fake('local');
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create();
    $file = UploadedFile::fake()->create('report.pdf', config('documents.max_size') / 1024 + 1, 'application/pdf');

    $this->actingAs($user)
        ->post(route('policies.documents.store', $policy), ['file' => $file])
        ->assertInvalid(['file']);

    $this->assertDatabaseCount('documents', 0);
});

test('rejects a file with a disallowed extension', function () {
    Storage::fake('local');
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create();
    $file = UploadedFile::fake()->create('video.mov', 100, 'video/quicktime');

    $this->actingAs($user)
        ->post(route('policies.documents.store', $policy), ['file' => $file])
        ->assertInvalid(['file']);

    $this->assertDatabaseCount('documents', 0);
});

test('authenticated user gets 404 for a policy from another organization', function () {
    Storage::fake('local');
    $user = User::factory()->withOrganization()->create();
    $otherOrganization = Organization::factory()->create();
    $policy = Policy::factory()->for($otherOrganization)->create();
    $file = UploadedFile::fake()->create('report.pdf', 100, 'application/pdf');

    $this->actingAs($user)
        ->post(route('policies.documents.store', $policy), ['file' => $file])
        ->assertNotFound();
});

test('stages the file and returns the pending document as json', function () {
    Storage::fake('local');
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create();
    $file = UploadedFile::fake()->create('report.pdf', 100, 'application/pdf');

    $this->actingAs($user)
        ->post(route('policies.documents.store', $policy), ['file' => $file])
        ->assertCreated()
        ->assertJson([
            'original_filename' => 'report.pdf',
            'status' => DocumentStatus::Pending->value,
        ]);

    $this->assertDatabaseHas('documents', [
        'documentable_type' => $policy->getMorphClass(),
        'documentable_id' => $policy->id,
        'uploaded_by' => $user->id,
    ]);
});
