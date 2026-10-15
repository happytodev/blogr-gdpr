<?php

/**
 * Issue #4 — the cookie banner must disappear when its own flag is off
 * (`cookie_consent.enabled`), and not only when the whole plugin is off.
 *
 * The banner is pushed into the layout's `cookie-consent` stack by a view
 * composer: we measure it by rendering the layout, the only reading that
 * proves the flag is really consulted.
 */
function renderBlogLayout(): string
{
    // The layout only needs the banner: we turn off what requires routes the
    // test environment does not mount (RSS feed, Blogr frontend).
    config([
        'blogr.rss.enabled' => false,
        'blogr.ui.navigation.show_theme_switcher' => false,
    ]);

    return view('blogr::layouts.blog', ['seoData' => null])->render();
}

it('regression_4_shows_the_cookie_banner_when_its_flag_is_on', function () {
    config(['blogr-gdpr.enabled' => true]);
    config(['blogr-gdpr.cookie_consent.enabled' => true]);

    expect(renderBlogLayout())->toContain('blogr-gdpr-cookie-consent');
});

it('regression_4_hides_the_cookie_banner_when_its_flag_is_off', function () {
    config(['blogr-gdpr.enabled' => true]);
    config(['blogr-gdpr.cookie_consent.enabled' => false]);

    expect(renderBlogLayout())->not->toContain('blogr-gdpr-cookie-consent');
});

it('regression_4_does_not_push_the_manage_preferences_link_without_a_consent_interface', function () {
    config([
        'blogr-gdpr.enabled' => true,
        'blogr-gdpr.cookie_consent.enabled' => false,
        'blogr-gdpr.analytics_consent.enabled' => false,
    ]);

    // The link calls the preferences window, which lives in the banner: with
    // no interface, it opens nothing (issue #4).
    expect(renderBlogLayout())->not->toContain('blogrGdprOpenPreferences');
});

it('regression_4_pushes_the_manage_preferences_link_when_a_consent_interface_is_on', function () {
    config([
        'blogr-gdpr.enabled' => true,
        'blogr-gdpr.cookie_consent.enabled' => false,
        'blogr-gdpr.analytics_consent.enabled' => true,
    ]);

    expect(renderBlogLayout())->toContain('blogrGdprOpenPreferences');
});

it('regression_4_still_hides_everything_when_the_plugin_itself_is_off', function () {
    config(['blogr-gdpr.enabled' => false]);
    config(['blogr-gdpr.cookie_consent.enabled' => true]);

    expect(renderBlogLayout())->not->toContain('blogr-gdpr-cookie-consent');
});
