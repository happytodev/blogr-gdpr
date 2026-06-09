<?php

use Happytodev\BlogrGdpr\Filament\Pages\GdprSettings;

it('loads data export and erasure settings from config', function () {
    config([
        'blogr-gdpr.data_export.enabled' => true,
        'blogr-gdpr.data_export.show_public_link' => true,
        'blogr-gdpr.data_erasure.enabled' => true,
        'blogr-gdpr.data_erasure.show_public_link' => true,
    ]);

    $page = new GdprSettings();
    $page->mount();

    expect($page->data_export_enabled)->toBeTrue();
    expect($page->data_export_show_link)->toBeTrue();
    expect($page->data_erasure_enabled)->toBeTrue();
    expect($page->data_erasure_show_link)->toBeTrue();
});

it('persists show_public_link values through save cycle', function () {
    config([
        'blogr-gdpr.data_export.enabled' => true,
        'blogr-gdpr.data_export.show_public_link' => true,
        'blogr-gdpr.data_erasure.enabled' => true,
        'blogr-gdpr.data_erasure.show_public_link' => true,
    ]);

    $page = new GdprSettings();
    $page->mount();

    $page->data_export_show_link = true;
    $page->data_erasure_show_link = true;
    $page->save();

    expect(config('blogr-gdpr.data_export.show_public_link'))->toBeTrue();
    expect(config('blogr-gdpr.data_erasure.show_public_link'))->toBeTrue();
});

it('persists show_public_link false through save cycle', function () {
    config([
        'blogr-gdpr.data_export.enabled' => true,
        'blogr-gdpr.data_export.show_public_link' => true,
        'blogr-gdpr.data_erasure.enabled' => true,
        'blogr-gdpr.data_erasure.show_public_link' => true,
    ]);

    $page = new GdprSettings();
    $page->mount();

    $page->data_export_show_link = false;
    $page->data_erasure_show_link = false;
    $page->save();

    expect(config('blogr-gdpr.data_export.show_public_link'))->toBeFalse();
    expect(config('blogr-gdpr.data_erasure.show_public_link'))->toBeFalse();
});

it('sets data_export_url and data_erasure_url after mounting', function () {
    config([
        'blogr-gdpr.data_export.enabled' => true,
        'blogr-gdpr.data_erasure.enabled' => true,
    ]);

    $page = new GdprSettings();
    $page->mount();

    expect($page->data_export_url)->not->toBeEmpty();
    expect($page->data_erasure_url)->not->toBeEmpty();
    expect($page->data_export_url)->toContain('/gdpr/data-export');
    expect($page->data_erasure_url)->toContain('/gdpr/data-erasure');
});

it('renders footer links after saving with show_link enabled', function () {
    config([
        'blogr-gdpr.data_export.enabled' => true,
        'blogr-gdpr.data_export.show_public_link' => true,
        'blogr-gdpr.data_erasure.enabled' => true,
        'blogr-gdpr.data_erasure.show_public_link' => true,
    ]);

    $page = new GdprSettings();
    $page->mount();
    $page->data_export_show_link = true;
    $page->data_erasure_show_link = true;
    $page->save();

    $html = view('blogr::components.footer')->render();

    expect($html)->toContain('Request my data');
    expect($html)->toContain('Request data deletion');
});

it('hides footer links after saving with show_link disabled', function () {
    config([
        'blogr-gdpr.data_export.enabled' => true,
        'blogr-gdpr.data_export.show_public_link' => true,
        'blogr-gdpr.data_erasure.enabled' => true,
        'blogr-gdpr.data_erasure.show_public_link' => true,
    ]);

    $page = new GdprSettings();
    $page->mount();
    $page->data_export_show_link = false;
    $page->data_erasure_show_link = false;
    $page->save();

    $html = view('blogr::components.footer')->render();

    expect($html)->not->toContain('Request my data');
    expect($html)->not->toContain('Request data deletion');
});
