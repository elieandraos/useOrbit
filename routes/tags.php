<?php

declare(strict_types=1);

use App\Http\Controllers\Documents\DocumentTagsController;
use App\Http\Controllers\Tags\TagsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'organization'])->prefix('tags')->name('tags.')->group(function () {
    Route::get('/', [TagsController::class, 'index'])->name('index');
    Route::post('/', [TagsController::class, 'store'])->name('store');
});

Route::middleware(['auth', 'verified', 'organization'])->group(function () {
    Route::post('documents/{document}/tags/{tag}', [DocumentTagsController::class, 'store'])->name('documents.tags.store');
    Route::delete('documents/{document}/tags/{tag}', [DocumentTagsController::class, 'destroy'])->name('documents.tags.destroy');
});
