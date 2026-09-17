<?php

declare(strict_types=1);

use App\Http\Controllers\Policies\PoliciesAutomotiveController;
use App\Http\Controllers\Policies\PoliciesAutomotivePdfExportController;
use App\Http\Controllers\Policies\PoliciesController;
use App\Http\Controllers\Policies\PoliciesExcelExportController;
use App\Http\Controllers\Policies\PoliciesExpatController;
use App\Http\Controllers\Policies\PoliciesExpatPdfExportController;
use App\Http\Controllers\Policies\PoliciesFireController;
use App\Http\Controllers\Policies\PoliciesFirePdfExportController;
use App\Http\Controllers\Policies\PoliciesLifeController;
use App\Http\Controllers\Policies\PoliciesLifePdfExportController;
use App\Http\Controllers\Policies\PoliciesMedicalController;
use App\Http\Controllers\Policies\PoliciesMedicalPdfExportController;
use App\Http\Controllers\Policies\PoliciesTravelController;
use App\Http\Controllers\Policies\PoliciesTravelPdfExportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'organization'])->group(function () {
    Route::get('policies', [PoliciesController::class, 'index'])->name('policies.index');
    Route::get('policies/create', [PoliciesController::class, 'create'])->name('policies.create');
    Route::get('policies/export', PoliciesExcelExportController::class)->name('policies.export');

    Route::get('policies/medical/create', [PoliciesMedicalController::class, 'create'])->name('policies.medical.create');
    Route::post('policies/medical', [PoliciesMedicalController::class, 'store'])->name('policies.medical.store');
    Route::get('policies/medical/{policy:slug}', [PoliciesMedicalController::class, 'show'])->name('policies.medical.show');
    Route::get('policies/medical/{policy:slug}/edit', [PoliciesMedicalController::class, 'edit'])->name('policies.medical.edit');
    Route::patch('policies/medical/{policy:slug}', [PoliciesMedicalController::class, 'update'])->name('policies.medical.update');
    Route::get('policies/medical/{policy:slug}/export', PoliciesMedicalPdfExportController::class)->name('policies.medical.export-pdf');

    Route::get('policies/automotive/create', [PoliciesAutomotiveController::class, 'create'])->name('policies.automotive.create');
    Route::post('policies/automotive', [PoliciesAutomotiveController::class, 'store'])->name('policies.automotive.store');
    Route::get('policies/automotive/{policy:slug}', [PoliciesAutomotiveController::class, 'show'])->name('policies.automotive.show');
    Route::get('policies/automotive/{policy:slug}/edit', [PoliciesAutomotiveController::class, 'edit'])->name('policies.automotive.edit');
    Route::patch('policies/automotive/{policy:slug}', [PoliciesAutomotiveController::class, 'update'])->name('policies.automotive.update');
    Route::get('policies/automotive/{policy:slug}/export', PoliciesAutomotivePdfExportController::class)->name('policies.automotive.export-pdf');

    Route::get('policies/expat/create', [PoliciesExpatController::class, 'create'])->name('policies.expat.create');
    Route::post('policies/expat', [PoliciesExpatController::class, 'store'])->name('policies.expat.store');
    Route::get('policies/expat/{policy:slug}', [PoliciesExpatController::class, 'show'])->name('policies.expat.show');
    Route::get('policies/expat/{policy:slug}/edit', [PoliciesExpatController::class, 'edit'])->name('policies.expat.edit');
    Route::patch('policies/expat/{policy:slug}', [PoliciesExpatController::class, 'update'])->name('policies.expat.update');
    Route::get('policies/expat/{policy:slug}/export', PoliciesExpatPdfExportController::class)->name('policies.expat.export-pdf');

    Route::get('policies/fire/create', [PoliciesFireController::class, 'create'])->name('policies.fire.create');
    Route::post('policies/fire', [PoliciesFireController::class, 'store'])->name('policies.fire.store');
    Route::get('policies/fire/{policy:slug}', [PoliciesFireController::class, 'show'])->name('policies.fire.show');
    Route::get('policies/fire/{policy:slug}/edit', [PoliciesFireController::class, 'edit'])->name('policies.fire.edit');
    Route::patch('policies/fire/{policy:slug}', [PoliciesFireController::class, 'update'])->name('policies.fire.update');
    Route::get('policies/fire/{policy:slug}/export', PoliciesFirePdfExportController::class)->name('policies.fire.export-pdf');

    Route::get('policies/life/create', [PoliciesLifeController::class, 'create'])->name('policies.life.create');
    Route::post('policies/life', [PoliciesLifeController::class, 'store'])->name('policies.life.store');
    Route::get('policies/life/{policy:slug}', [PoliciesLifeController::class, 'show'])->name('policies.life.show');
    Route::get('policies/life/{policy:slug}/edit', [PoliciesLifeController::class, 'edit'])->name('policies.life.edit');
    Route::patch('policies/life/{policy:slug}', [PoliciesLifeController::class, 'update'])->name('policies.life.update');
    Route::get('policies/life/{policy:slug}/export', PoliciesLifePdfExportController::class)->name('policies.life.export-pdf');

    Route::get('policies/travel/create', [PoliciesTravelController::class, 'create'])->name('policies.travel.create');
    Route::post('policies/travel', [PoliciesTravelController::class, 'store'])->name('policies.travel.store');
    Route::get('policies/travel/{policy:slug}', [PoliciesTravelController::class, 'show'])->name('policies.travel.show');
    Route::get('policies/travel/{policy:slug}/edit', [PoliciesTravelController::class, 'edit'])->name('policies.travel.edit');
    Route::patch('policies/travel/{policy:slug}', [PoliciesTravelController::class, 'update'])->name('policies.travel.update');
    Route::get('policies/travel/{policy:slug}/export', PoliciesTravelPdfExportController::class)->name('policies.travel.export-pdf');
});
