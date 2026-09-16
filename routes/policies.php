<?php

declare(strict_types=1);

use App\Http\Controllers\Policies\PoliciesController;
use App\Http\Controllers\Policies\PoliciesMedicalController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'organization'])->group(function () {
    Route::get('policies', [PoliciesController::class, 'index'])->name('policies.index');

    Route::get('policies/medical/create', [PoliciesMedicalController::class, 'create'])->name('policies.medical.create');
    Route::post('policies/medical', [PoliciesMedicalController::class, 'store'])->name('policies.medical.store');
    Route::get('policies/medical/{policy:slug}', [PoliciesMedicalController::class, 'show'])->name('policies.medical.show');
    Route::get('policies/medical/{policy:slug}/edit', [PoliciesMedicalController::class, 'edit'])->name('policies.medical.edit');
    Route::patch('policies/medical/{policy:slug}', [PoliciesMedicalController::class, 'update'])->name('policies.medical.update');
});
