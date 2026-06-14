<?php

use Illuminate\Support\Facades\Route;

if (app()->environment('local')) {
    Route::get('/design-foundation', fn () => redirect('/design-foundation/button'))->name('design-foundation');
    Route::get('/design-foundation/button', fn () => inertia('design-foundation/button/Index'))->name('design-foundation.button');
    Route::get('/design-foundation/badge', fn () => inertia('design-foundation/badge/Index'))->name('design-foundation.badge');
    Route::get('/design-foundation/input', fn () => inertia('design-foundation/input/Index'))->name('design-foundation.input');
    Route::get('/design-foundation/textarea', fn () => inertia('design-foundation/textarea/Index'))->name('design-foundation.textarea');
    Route::get('/design-foundation/select', fn () => inertia('design-foundation/select/Index'))->name('design-foundation.select');
    Route::get('/design-foundation/label', fn () => inertia('design-foundation/label/Index'))->name('design-foundation.label');
    Route::get('/design-foundation/checkbox', fn () => inertia('design-foundation/checkbox/Index'))->name('design-foundation.checkbox');
}
