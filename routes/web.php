<?php

use Happytodev\BlogrGdpr\Http\Controllers\GdprController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function () {
    Route::post('/gdpr/consent', [GdprController::class, 'storeConsent'])->name('gdpr.consent');
    Route::post('/gdpr/withdraw', [GdprController::class, 'withdrawConsent'])->name('gdpr.withdraw');
    Route::get('/gdpr/data-export', [GdprController::class, 'showDataExport'])->name('gdpr.data-export');
    Route::post('/gdpr/data-export', [GdprController::class, 'requestDataExport'])->name('gdpr.data-export.request');
    Route::get('/gdpr/data-erasure', [GdprController::class, 'showDataErasure'])->name('gdpr.data-erasure');
    Route::post('/gdpr/data-erasure', [GdprController::class, 'requestDataErasure'])->name('gdpr.data-erasure.request');
});
