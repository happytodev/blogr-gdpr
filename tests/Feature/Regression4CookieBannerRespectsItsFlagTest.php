<?php

/**
 * Issue #4 — le bandeau cookies doit disparaître quand son propre drapeau est
 * éteint (`cookie_consent.enabled`), et pas seulement quand on éteint tout le plugin.
 *
 * Le bandeau est poussé dans la pile `cookie-consent` de la mise en page par un
 * compositeur de vues : on le mesure en rendant la mise en page, la seule lecture
 * qui prouve que le drapeau est réellement consulté.
 */
function renderBlogLayout(): string
{
    // La mise en page n'a besoin que du bandeau : on éteint ce qui réclame des
    // routes que l'environnement de test ne monte pas (flux RSS, frontend Blogr).
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

    // Le lien appelle la fenêtre de préférences, qui vit dans le bandeau : sans
    // interface, il n'ouvre rien (issue #4).
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
