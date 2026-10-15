<?php

namespace Happytodev\BlogrGdpr\Services;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * The analytics choice, server side.
 *
 * The consent was kept in the visitor's `localStorage` only: the server could
 * therefore not know that a visitor had declined, and served the provider's
 * tag to everyone. Recording the choice **also** in a cookie makes the
 * blocking possible at the source, without relying on JavaScript: either the
 * page goes out without the tag, or it goes out with it (issue #6).
 *
 * The cookie serves that purpose only. It is set by the server on the consent
 * or withdrawal call, never when a page is loaded.
 */
class AnalyticsConsentCookie
{
    public const NAME = 'blogr_gdpr_analytics';

    public const ACCEPTED = 'accepted';

    public const DECLINED = 'declined';

    /**
     * Six months: the usual lifetime of a consent proof. Withdrawal removes it,
     * and the visitor can decline again.
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
