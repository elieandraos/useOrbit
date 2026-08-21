<?php

declare(strict_types=1);

use App\Http\Controllers\Notifications\NotifiableMembersController;
use App\Http\Controllers\Notifications\NotificationsIndexController;
use App\Http\Controllers\Notifications\NotificationsListController;
use App\Http\Controllers\Notifications\NotificationsMarkAllReadController;
use App\Http\Controllers\Notifications\NotificationsMarkReadController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'organization'])->prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', NotificationsIndexController::class)->name('index');
    Route::get('recent', NotificationsListController::class)->name('recent');
    Route::post('read-all', NotificationsMarkAllReadController::class)->name('read-all');
    Route::post('{notification}/read', NotificationsMarkReadController::class)->name('read');
});

Route::middleware(['auth', 'organization'])->prefix('notify')->name('notify.')->group(function () {
    Route::get('recipients', NotifiableMembersController::class)->name('recipients');
});
