<?php

namespace Happytodev\BlogrGdpr\Services;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Le choix de mesure d'audience, côté serveur.
 *
 * Le consentement n'était conservé que dans le `localStorage` du visiteur : le
 * serveur ne pouvait donc pas savoir qu'un visiteur avait refusé, et servait la
 * balise du fournisseur à tout le monde. Enregistrer le choix **aussi** dans un
 * cookie rend le blocage possible à la source, sans dépendre du JavaScript : soit
 * la page part sans la balise, soit elle part avec (issue #6).
 *
 * Le cookie ne sert qu'à ça. Il est posé par le serveur sur l'appel de
 * consentement ou de retrait, jamais par le chargement d'une page.
 */
class AnalyticsConsentCookie
{
    public const NAME = 'blogr_gdpr_analytics';

    public const ACCEPTED = 'accepted';

    public const DECLINED = 'declined';

    /**
     * Six mois : la durée d'une preuve de consentement habituelle. Le retrait la
     * supprime, et le visiteur peut refuser à nouveau.
     */
    public const LIFETIME_MINUTES = 60 * 24 * 180;

    public static function choice(Request $request): ?string
    {
        $value = $request->cookie(self::NAME);

        return in_array($value, [self::ACCEPTED, self::DECLINED], true) ? $value : null;
    }

    public static function hasAccepted(Request $request): bool
    {
        return self::choice($request) === self::ACCEPTED;
    }

    public static function accept(Response|RedirectResponse $response): Response|RedirectResponse
    {
        return self::put($response, self::ACCEPTED);
    }

    public static function decline(Response|RedirectResponse $response): Response|RedirectResponse
    {
        return self::put($response, self::DECLINED);
    }

    private static function put(Response|RedirectResponse $response, string $value): Response|RedirectResponse
    {
        return $response->withCookie(cookie(
            name: self::NAME,
            value: $value,
            minutes: self::LIFETIME_MINUTES,
            path: config('session.path', '/'),
            domain: config('session.domain'),
            secure: app()->environment('production'),
            httpOnly: false,   // la bannière du plugin doit pouvoir relire le choix
            raw: false,
            sameSite: 'lax',
        ));
    }
}
