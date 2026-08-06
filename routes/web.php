<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => inertia('Welcome'))->name('home');

Route::middleware(['auth', 'verified', 'organization'])->group(function () {
    Route::get('dashboard', fn () => inertia('Dashboard'))->name('dashboard');
});

require __DIR__.'/agents.php';
require __DIR__.'/carriers.php';
require __DIR__.'/clients.php';
require __DIR__.'/documents.php';
require __DIR__.'/notes.php';
require __DIR__.'/notifications.php';
require __DIR__.'/organization-invitations.php';
require __DIR__.'/organization-members.php';
require __DIR__.'/tags.php';
require __DIR__.'/world.php';
require __DIR__.'/settings.php';
require __DIR__.'/dev.php';
