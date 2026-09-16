<?php

declare(strict_types=1);

use App\Http\Resources\NoteResource;
use App\Http\Resources\PolicyResource;
use App\Models\Note;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $policy = Policy::factory()->medical()->create();

    $this->get(route('policies.notes.index', $policy))
        ->assertRedirect(route('login'));
});

test('authenticated user gets 404 for a policy from another organization', function () {
    $user = User::factory()->withOrganization()->create();

    $otherOrganization = Organization::factory()->create();
    $policy = Policy::factory()->medical()->create(['organization_id' => $otherOrganization->id]);

    $this->actingAs($user)
        ->get(route('policies.notes.index', $policy))
        ->assertNotFound();
});

test('notes are listed pinned-first then newest', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id]);

    $older = Note::factory()->forOrganization($user)->createdBy($user)->create([
        'notable_type' => $policy->getMorphClass(),
        'notable_id' => $policy->id,
        'created_at' => now()->subDay(),
    ]);
    $newer = Note::factory()->forOrganization($user)->createdBy($user)->create([
        'notable_type' => $policy->getMorphClass(),
        'notable_id' => $policy->id,
        'created_at' => now(),
    ]);
    $pinned = Note::factory()->forOrganization($user)->createdBy($user)->pinned()->create([
        'notable_type' => $policy->getMorphClass(),
        'notable_id' => $policy->id,
        'created_at' => now()->subWeek(),
    ]);

    $this->actingAs($user)
        ->get(route('policies.notes.index', $policy))
        ->assertOk()
        ->assertHasResource('policy', PolicyResource::make($policy->load(['client', 'carrier', 'agent'])))
        ->assertHasResource(
            'notes',
            NoteResource::collection(
                $policy->notes()->with('createdBy')->orderByDesc('pinned')->latest()->orderByDesc('id')->get()
            )
        )
        ->assertInertia(fn ($page) => $page->where('notes.0.id', $pinned->id)
            ->where('notes.1.id', $newer->id)
            ->where('notes.2.id', $older->id)
        );
});

test('notes from another policy are not included', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id]);
    $otherPolicy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id]);

    Note::factory(2)->forOrganization($user)->createdBy($user)->create([
        'notable_type' => $policy->getMorphClass(),
        'notable_id' => $policy->id,
    ]);
    Note::factory(3)->forOrganization($user)->createdBy($user)->create([
        'notable_type' => $otherPolicy->getMorphClass(),
        'notable_id' => $otherPolicy->id,
    ]);

    $this->actingAs($user)
        ->get(route('policies.notes.index', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('notes', 2));
});

test('shares the note config for the composer character counter', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('policies.notes.index', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('noteConfig.max_length', config('notes.max_length')));
});
