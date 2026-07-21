<?php

declare(strict_types=1);

use App\Http\Controllers\Notifications\NotificationsMarkAllReadController;
use App\Http\Controllers\Notifications\NotificationsMarkReadController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'organization'])->prefix('notifications')->name('notifications.')->group(function () {
    Route::post('read-all', NotificationsMarkAllReadController::class)->name('read-all');
    Route::post('{notification}/read', NotificationsMarkReadController::class)->name('read');
});
