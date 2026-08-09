<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Note;
use App\Models\User;

test('note belongs to its notable and resolves the relation both directions', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create();
    $note = Note::factory()->forOrganization($user)->createdBy($user)->create([
        'notable_type' => $client->getMorphClass(),
        'notable_id' => $client->id,
    ]);

    expect($note->notable)->toBeInstanceOf(Client::class)
        ->and($note->notable->is($client))->toBeTrue()
        ->and($client->notes->pluck('id'))->toContain($note->id);
});

test('note belongs to its creator', function () {
    $user = User::factory()->withOrganization()->create();
    $note = Note::factory()->forOrganization($user)->createdBy($user)->create();

    expect($note->createdBy)->toBeInstanceOf(User::class)
        ->and($note->createdBy->is($user))->toBeTrue();
});

test('current organization scope only returns notes for the acting user\'s current organization', function () {
    $user = User::factory()->withOrganization()->create();
    $otherUser = User::factory()->withOrganization()->create();

    $ownNote = Note::factory()->forOrganization($user)->createdBy($user)->create();
    Note::factory()->forOrganization($otherUser)->createdBy($otherUser)->create();

    setOrganizationContext($user);

    expect(Note::query()->pluck('id'))->toEqual(collect([$ownNote->id]));
});
