<?php

use Illuminate\Support\Facades\Route;

if (app()->environment('local')) {
    Route::get('/design-foundation', fn () => redirect('/design-foundation/button'))->name('design-foundation');
    Route::get('/design-foundation/button', fn () => inertia('design-foundation/button/Index'))->name('design-foundation.button');
    Route::get('/design-foundation/badge', fn () => inertia('design-foundation/badge/Index'))->name('design-foundation.badge');
}
