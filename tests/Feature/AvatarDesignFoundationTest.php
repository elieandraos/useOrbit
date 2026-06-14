<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

test('avatar design foundation page is accessible in local environment', function () {
    Route::get('/design-foundation/avatar', fn () => inertia('design-foundation/avatar/Index'));

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/design-foundation/avatar')
        ->assertStatus(200);
});
