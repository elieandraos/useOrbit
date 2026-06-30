<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

if (app()->environment('local')) {
    Route::get('/design-foundation', fn () => redirect('/design-foundation/button'))->name('design-foundation');
    Route::get('/design-foundation/button', fn () => inertia('design-foundation/button/Index'))->name('design-foundation.button');
    Route::get('/design-foundation/badge', fn () => inertia('design-foundation/badge/Index'))->name('design-foundation.badge');
    Route::get('/design-foundation/breadcrumbs', fn () => inertia('design-foundation/breadcrumbs/Index'))->name('design-foundation.breadcrumbs');
    Route::get('/design-foundation/input', fn () => inertia('design-foundation/input/Index'))->name('design-foundation.input');
    Route::get('/design-foundation/textarea', fn () => inertia('design-foundation/textarea/Index'))->name('design-foundation.textarea');
    Route::get('/design-foundation/select', fn () => inertia('design-foundation/select/Index'))->name('design-foundation.select');
    Route::get('/design-foundation/label', fn () => inertia('design-foundation/label/Index'))->name('design-foundation.label');
    Route::get('/design-foundation/checkbox', fn () => inertia('design-foundation/checkbox/Index'))->name('design-foundation.checkbox');
    Route::get('/design-foundation/avatar', fn () => inertia('design-foundation/avatar/Index'))->name('design-foundation.avatar');
    Route::get('/design-foundation/separator', fn () => inertia('design-foundation/separator/Index'))->name('design-foundation.separator');
    Route::get('/design-foundation/drop-menu', fn () => inertia('design-foundation/drop-menu/Index'))->name('design-foundation.drop-menu');
    Route::get('/design-foundation/date-input', fn () => inertia('design-foundation/date-input/Index'))->name('design-foundation.date-input');
    Route::get('/design-foundation/dialog', fn () => inertia('design-foundation/dialog/Index'))->name('design-foundation.dialog');
    Route::get('/design-foundation/card', fn () => inertia('design-foundation/card/Index'))->name('design-foundation.card');
    Route::get('/design-foundation/form-field', fn () => inertia('design-foundation/form-field/Index'))->name('design-foundation.form-field');
}
