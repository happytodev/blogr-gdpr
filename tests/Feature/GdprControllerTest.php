<?php

namespace Happytodev\BlogrGdpr\Tests\Feature;

use Happytodev\BlogrGdpr\Models\ConsentLog;
use Happytodev\BlogrGdpr\Models\GdprRequest;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    if (!Route::has('blog.feed')) {
        Route::get('/feed', fn () => 'feed')->name('blog.feed');
    }
    config()->set('blogr-gdpr.consent_log.enabled', true);
    config()->set('blogr-gdpr.dpo.email', 'dpo@example.com');
    config()->set('mail.default', 'array');
});

it('can store consent via the consent endpoint', function () {
    $this
        ->post(route('gdpr.consent'), [
            'consent_type' => 'cookies',
            'consent_data' => [],
        ])
        ->assertSessionHas('blogr_gdpr_consent_cookies', true);
});

it('can withdraw consent via the withdraw endpoint', function () {
    session()->put('blogr_gdpr_consent_cookies', true);

    $this
        ->post(route('gdpr.withdraw'), [
            'consent_type' => 'cookies',
        ])
        ->assertSessionMissing('blogr_gdpr_consent_cookies');
});

it('renders the data export form', function () {
    $this
        ->get(route('gdpr.data-export'))
        ->assertStatus(200);
});

it('renders the data erasure form', function () {
    $this
        ->get(route('gdpr.data-erasure'))
        ->assertStatus(200);
});

it('can request data export', function () {
    $this
        ->post(route('gdpr.data-export.request'), [
            'email' => 'user@example.com',
        ]);

    expect(GdprRequest::where('email', 'user@example.com')->where('request_type', 'export')->exists())->toBeTrue();
});

it('validates the email for data export', function () {
    $this
        ->post(route('gdpr.data-export.request'), [
            'email' => 'not-an-email',
        ])
        ->assertSessionHasErrors('email');
});

it('can request data erasure with confirmation', function () {
    $this
        ->from(route('gdpr.data-erasure'))
        ->post(route('gdpr.data-erasure.request'), [
            'email' => 'user@example.com',
            'confirmation' => '1',
        ]);

    expect(GdprRequest::where('email', 'user@example.com')->where('request_type', 'erasure')->exists())->toBeTrue();
});

it('requires confirmation for data erasure', function () {
    $this
        ->post(route('gdpr.data-erasure.request'), [
            'email' => 'user@example.com',
        ])
        ->assertSessionHasErrors('confirmation');
});

it('logs consent to the database', function () {
    $this
        ->post(route('gdpr.consent'), [
            'consent_type' => 'analytics',
        ]);

    expect(ConsentLog::where('consent_type', 'analytics')->where('consent_given', true)->exists())->toBeTrue();
});
