<?php

namespace Happytodev\BlogrGdpr\Tests\Feature;

use Happytodev\Blogr\Services\ExtensionRegistry;
use Happytodev\BlogrGdpr\Filament\Pages\GdprSettings;
use Happytodev\BlogrGdpr\Filament\Resources\ConsentLogResource;
use Happytodev\BlogrGdpr\Filament\Resources\GdprRequestResource;

beforeEach(function () {
    $this->registry = app(ExtensionRegistry::class);

    // Register routes required by view composers
    app('router')->get('feed', function () {})->name('blog.feed');
    app('router')->get('cms/{slug}', function () {})->name('cms.page.show');
    app('router')->get('/blog', function () {})->name('blog.index');
    app('router')->post('/contact/submit', function () {})->name('blogr.cms.contact.submit');
});

it('can disable blogr-gdpr via the extension registry', function () {
    expect($this->registry->isEnabled('blogr-gdpr'))->toBeTrue();

    $this->registry->disable('blogr-gdpr');

    expect($this->registry->isEnabled('blogr-gdpr'))->toBeFalse();
});

it('does not render cookie consent HTML when extension is disabled', function () {
    $this->registry->disable('blogr-gdpr');

    $html = view('blogr::layouts.blog')->render();

    expect($html)->not->toContain('blogr-gdpr-cookie-consent');
});

it('does not render analytics consent HTML when extension is disabled', function () {
    config(['blogr-gdpr.analytics_consent' => [
        'enabled' => true,
        'providers' => ['google-analytics'],
        'position' => 'body',
    ]]);
    config(['blogr.analytics.provider' => 'google-analytics']);

    $this->registry->disable('blogr-gdpr');

    $html = view('blogr::layouts.blog')->render();

    expect($html)->not->toContain('blogr-gdpr-analytics-consent');
});

it('does not render contact form consent HTML when extension is disabled', function () {
    config(['blogr-gdpr.contact_consent.enabled' => true]);

    $this->registry->disable('blogr-gdpr');

    $html = view('blogr::components.blocks.contact_form', ['data' => []])->render();

    expect($html)->not->toContain('contact-form-consent');
});

it('does not render footer privacy link when extension is disabled', function () {
    config(['blogr-gdpr.privacy_policy.auto_create' => true]);

    $this->registry->disable('blogr-gdpr');

    $html = view('blogr::components.footer')->render();

    expect($html)->not->toContain('Privacy');
});

it('renders cookie consent HTML when extension is enabled', function () {
    expect($this->registry->isEnabled('blogr-gdpr'))->toBeTrue();

    $html = view('blogr::layouts.blog')->render();

    expect($html)->toContain('blogr-gdpr-cookie-consent');
});

it('renders analytics consent HTML when extension is enabled and configured', function () {
    config(['blogr-gdpr.analytics_consent' => [
        'enabled' => true,
        'providers' => ['google-analytics'],
        'position' => 'body',
    ]]);
    config(['blogr.analytics.provider' => 'google-analytics']);

    expect($this->registry->isEnabled('blogr-gdpr'))->toBeTrue();

    $html = view('blogr::layouts.blog')->render();

    expect($html)->toContain('blogr-gdpr-analytics-consent');
});

it('hides gdpr request resource navigation when extension is disabled', function () {
    expect(GdprRequestResource::shouldRegisterNavigation())->toBeTrue();

    $this->registry->disable('blogr-gdpr');

    expect(GdprRequestResource::shouldRegisterNavigation())->toBeFalse();
});

it('hides consent log resource navigation when extension is disabled', function () {
    expect(ConsentLogResource::shouldRegisterNavigation())->toBeTrue();

    $this->registry->disable('blogr-gdpr');

    expect(ConsentLogResource::shouldRegisterNavigation())->toBeFalse();
});

it('hides gdpr settings page navigation when extension is disabled', function () {
    expect(GdprSettings::shouldRegisterNavigation())->toBeTrue();

    $this->registry->disable('blogr-gdpr');

    expect(GdprSettings::shouldRegisterNavigation())->toBeFalse();
});
