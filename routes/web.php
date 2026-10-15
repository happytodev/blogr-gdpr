<?php

use Happytodev\BlogrGdpr\Http\Controllers\GdprController;
use Illuminate\Support\Facades\Route;

// Les pages s'affichent dans la langue du site : les deux formulaires d'accès et
// de suppression sont donc aussi servis sous /{locale}/gdpr/… (issue #7). Les
// adresses sans préfixe restent en place : les liens déjà publiés, les envois de
// formulaires et le JavaScript du bandeau continuent de fonctionner tels quels.
$availableLocales = config('blogr.locales.available', []);
$localeConstraint = $availableLocales === []
    ? '[a-z]{2}'
    : implode('|', array_map(fn ($locale) => preg_quote((string) $locale, '/'), $availableLocales));

$localizedMiddleware = class_exists(\Happytodev\Blogr\Http\Middleware\SetLocale::class)
    ? [\Happytodev\Blogr\Http\Middleware\SetLocale::class]
    : [];

Route::middleware(['web', 'throttle:10,60'])->group(function () {
    Route::post('/gdpr/consent', [GdprController::class, 'storeConsent'])->name('gdpr.consent');
    Route::post('/gdpr/withdraw', [GdprController::class, 'withdrawConsent'])->name('gdpr.withdraw');
    Route::get('/gdpr/data-export', [GdprController::class, 'showDataExport'])->name('gdpr.data-export');
    Route::post('/gdpr/data-export', [GdprController::class, 'requestDataExport'])->name('gdpr.data-export.request');
    Route::get('/gdpr/data-erasure', [GdprController::class, 'showDataErasure'])->name('gdpr.data-erasure');
    Route::post('/gdpr/data-erasure', [GdprController::class, 'requestDataErasure'])->name('gdpr.data-erasure.request');
});

Route::middleware(array_merge(['web', 'throttle:10,60'], $localizedMiddleware))->group(function () use ($localeConstraint) {
    Route::get('/{locale}/gdpr/data-export', [GdprController::class, 'showDataExport'])
        ->where('locale', $localeConstraint)
        ->name('gdpr.data-export.localized');
    Route::get('/{locale}/gdpr/data-erasure', [GdprController::class, 'showDataErasure'])
        ->where('locale', $localeConstraint)
        ->name('gdpr.data-erasure.localized');
});
