<?php

namespace Happytodev\BlogrGdpr\Services;

use Happytodev\BlogrGdpr\Models\ConsentLog;

class ConsentService
{
    public function logConsent(
        string $consentType,
        bool $consentGiven,
        ?string $email = null,
        ?array $consentData = null,
    ): ConsentLog {
        $request = request();

        return ConsentLog::create([
            'email' => $email,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'consent_type' => $consentType,
            'consent_given' => $consentGiven,
            'consent_data' => $consentData,
        ]);
    }

    public function hasConsent(string $consentType): bool
    {
        if (! config('blogr-gdpr.consent_log.enabled')) {
            return true;
        }

        if (! session()->has("blogr_gdpr_consent_{$consentType}")) {
            return false;
        }

        return session()->get("blogr_gdpr_consent_{$consentType}") === true;
    }

    public function giveConsent(string $consentType, ?array $data = null): void
    {
        session()->put("blogr_gdpr_consent_{$consentType}", true);

        $this->logConsent($consentType, true, null, $data);
    }

    public function withdrawConsent(string $consentType): void
    {
        session()->forget("blogr_gdpr_consent_{$consentType}");

        $this->logConsent($consentType, false);
    }

    public function getConsentTypes(): array
    {
        return [
            'cookies' => __('blogr-gdpr::messages.consent_types.cookies'),
            'analytics' => __('blogr-gdpr::messages.consent_types.analytics'),
            'contact' => __('blogr-gdpr::messages.consent_types.contact'),
        ];
    }
}
