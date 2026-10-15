<?php

use Happytodev\BlogrGdpr\Services\AnalyticsConsentCookie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Testing\TestResponse;

/**
 * Issue #6 — the analytics consent was recorded (in the visitor's
 * `localStorage`) but the provider's tag went out anyway: the choice blocked
 * nothing. The server could not know about it, for want of a cookie. And the
 * functions the buttons call (`blogrGdprShowAnalytics`,
 * `blogrGdprHideAnalytics`) were defined nowhere.
 */
beforeEach(function () {
    config([
        'blogr-gdpr.enabled' => true,
        'blogr-gdpr.analytics_consent.enabled' => true,
        'blogr.analytics.enabled' => true,
        'blogr.analytics.provider' => 'umami',
        'blogr.analytics.umami.website_id' => 'test-website-id',
        'blogr.analytics.umami.src' => 'https://cloud.umami.is/script.js',
    ]);
});

function trackerPage(?string $cookieChoice = null): string
{
    $cookies = $cookieChoice ? [AnalyticsConsentCookie::NAME => $cookieChoice] : [];
    app()->instance('request', Request::create('/', 'GET', [], $cookies));

    // Rendering a package view in isolation sometimes leaves an output buffer
    // open in the harness: we close it so the test is not marked "risky".
    $bufferLevel = ob_get_level();

    $html = Blade::render(
        "{!! app('view')->make('blogr::components.analytics-tracker')->render() !!}".
        "@stack('analytics-consent')@stack('analytics-after')@stack('scripts')"
    );

    while (ob_get_level() > $bufferLevel) {
        ob_end_clean();
    }

    return $html;
}

it('regression_6_serves_no_analytics_tag_while_no_choice_was_made', function () {
    $html = trackerPage();

    expect($html)->not->toContain('test-website-id')
        ->and($html)->not->toContain('cloud.umami.is')
        ->and($html)->toContain('blogr-gdpr-analytics-consent');
});

it('regression_6_serves_the_tag_once_analytics_was_accepted', function () {
    $html = trackerPage(AnalyticsConsentCookie::ACCEPTED);

    expect($html)->toContain('test-website-id')
        ->and($html)->not->toContain('blogr-gdpr-analytics-consent');
});

it('regression_6_does_not_serve_the_tag_when_analytics_was_declined', function () {
    $html = trackerPage(AnalyticsConsentCookie::DECLINED);

    expect($html)->not->toContain('test-website-id')
        ->and($html)->toContain('blogr-gdpr-analytics-consent');
});

it('regression_6_defines_the_functions_the_banner_calls', function () {
    $html = trackerPage();

    expect($html)->toContain('window.blogrGdprAcceptAnalytics')
        ->and($html)->toContain('window.blogrGdprDeclineAnalytics')
        ->and($html)->toContain('window.blogrGdprShowAnalytics')
        ->and($html)->toContain('window.blogrGdprHideAnalytics');
});

it('regression_6_stores_the_choice_in_a_cookie_the_server_can_read', function () {
    $this->post(route('gdpr.consent'), ['consent_type' => 'analytics'])
        ->assertCookie(AnalyticsConsentCookie::NAME, AnalyticsConsentCookie::ACCEPTED);

    $this->post(route('gdpr.withdraw'), ['consent_type' => 'analytics'])
        ->assertCookie(AnalyticsConsentCookie::NAME, AnalyticsConsentCookie::DECLINED);
});
