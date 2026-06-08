<?php

namespace Happytodev\BlogrGdpr\Tests\Feature;

use Happytodev\BlogrGdpr\Models\ConsentLog;
use Happytodev\BlogrGdpr\Services\ConsentService;

beforeEach(function () {
    config()->set('blogr-gdpr.consent_log.enabled', true);
});

it('can log consent', function () {
    $service = app(ConsentService::class);

    $log = $service->logConsent('cookies', true, 'test@example.com', ['preferences' => 'all']);

    expect($log)->toBeInstanceOf(ConsentLog::class);
    expect($log->consent_type)->toBe('cookies');
    expect($log->consent_given)->toBeTrue();
    expect($log->email)->toBe('test@example.com');
});

it('can give consent and store in session', function () {
    $service = app(ConsentService::class);

    $service->giveConsent('analytics', ['enabled' => true]);

    expect(session()->get('blogr_gdpr_consent_analytics'))->toBeTrue();
});

it('can withdraw consent', function () {
    $service = app(ConsentService::class);

    $service->giveConsent('cookies');
    $service->withdrawConsent('cookies');

    expect(session()->get('blogr_gdpr_consent_cookies'))->not->toBeTrue();
});

it('checks if consent is given', function () {
    $service = app(ConsentService::class);

    expect($service->hasConsent('cookies'))->toBeFalse();

    $service->giveConsent('cookies');

    expect($service->hasConsent('cookies'))->toBeTrue();
});

it('returns true for consent if logging is disabled', function () {
    config()->set('blogr-gdpr.consent_log.enabled', false);

    $service = app(ConsentService::class);

    expect($service->hasConsent('cookies'))->toBeTrue();
});

it('returns all consent types', function () {
    $service = app(ConsentService::class);

    $types = $service->getConsentTypes();

    expect($types)->toHaveKeys(['cookies', 'analytics', 'contact']);
});
