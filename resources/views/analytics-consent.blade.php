@php
    $provider = config('blogr.analytics.provider');
    $providerLabel = __('blogr-gdpr::messages.providers.' . $provider . '.label');
    $providerPro = __('blogr-gdpr::messages.providers.' . $provider . '.pro');
    $providerCon = __('blogr-gdpr::messages.providers.' . $provider . '.con');
    $position = config('blogr-gdpr.cookie_consent.position', 'bottom');
    $isDark = config('blogr-gdpr.cookie_consent.theme', 'dark') === 'dark';
    $bgColor = $isDark ? '#1a1a2e' : '#ffffff';
    $textColor = $isDark ? '#e0e0e0' : '#333333';
    $borderColor = $isDark ? '#333' : '#e0e0e0';
    $positionStyles = $position === 'bottom'
        ? 'bottom: 0; left: 0; right: 0; border-top: 1px solid ' . $borderColor . ';'
        : 'top: 0; left: 0; right: 0; border-bottom: 1px solid ' . $borderColor . ';';

    // The cookie tells whether the visitor already accepted: if so, the
    // provider's tag is served and this bar has nothing left to ask.
    $accepted = $accepted ?? false;
@endphp

@if(! $accepted)
<div id="blogr-gdpr-analytics-consent" style="position: fixed; {{ $positionStyles }} z-index: 9998; padding: 16px; background: {{ $bgColor }}; color: {{ $textColor }}; box-shadow: 0 -2px 10px rgba(0,0,0,0.1); font-family: system-ui, -apple-system, sans-serif; font-size: 14px; line-height: 1.5;">
    <div style="max-width: 1200px; margin: 0 auto;">
        <p style="margin: 0 0 6px 0; font-weight: 600;">
            {{ __('blogr-gdpr::messages.analytics_consent_title', ['provider' => $providerLabel]) }}
        </p>
        <p style="margin: 0 0 6px 0; font-size: 13px; color: {{ $isDark ? '#aaa' : '#666' }};">
            {{ __('blogr-gdpr::messages.analytics_consent_text', ['provider' => $providerLabel]) }}
        </p>
        @if($providerPro)
        <p style="margin: 0 0 2px 0; font-size: 12px; color: {{ $isDark ? '#aaa' : '#666' }};">
            <span style="color: #22c55e;">&#10003;</span> {{ $providerPro }}
        </p>
        @endif
        @if($providerCon)
        <p style="margin: 0 0 6px 0; font-size: 12px; color: {{ $isDark ? '#888' : '#999' }};">
            <span style="color: #f97316;">&#9888;</span> {{ $providerCon }}
        </p>
        @endif

        {{--
            Deux formulaires plutôt que deux boutons JavaScript : c'est le serveur
            qui enregistre le choix (et pose le cookie), donc il n'y a aucun état
            intermédiaire où la mesure tournerait sans consentement (issue #6).
        --}}
        <div style="display: flex; gap: 8px; margin-top: 10px;">
            <form method="POST" action="{{ route('gdpr.consent') }}" style="margin: 0;">
                @csrf
                <input type="hidden" name="consent_type" value="analytics">
                <button type="submit" style="padding: 8px 20px; border: none; border-radius: 4px; cursor: pointer; background: var(--color-primary, #2563eb); color: #ffffff; font-size: 14px; font-weight: 500;">
                    {{ __('blogr-gdpr::messages.accept') }}
                </button>
            </form>
            <form method="POST" action="{{ route('gdpr.withdraw') }}" style="margin: 0;">
                @csrf
                <input type="hidden" name="consent_type" value="analytics">
                <button type="submit" style="padding: 8px 20px; border: 1px solid {{ $isDark ? '#555' : '#ccc' }}; border-radius: 4px; cursor: pointer; background: transparent; color: inherit; font-size: 14px;">
                    {{ __('blogr-gdpr::messages.decline') }}
                </button>
            </form>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
{{--
    Ces fonctions sont appelées par la fenêtre « gérer mes préférences » du bandeau.
    Elles n'existaient nulle part : accepter la mesure enregistrait un choix et
    laissait la mesure tourner (issue #6). Elles passent maintenant par le serveur,
    qui pose le cookie et recharge la page — la balise est alors servie, ou pas.
--}}
window.blogrGdprAcceptAnalytics = function () {
    window.blogrGdprPostAnalyticsChoice('{{ route('gdpr.consent') }}');
};

window.blogrGdprDeclineAnalytics = function () {
    window.blogrGdprPostAnalyticsChoice('{{ route('gdpr.withdraw') }}');
};

window.blogrGdprShowAnalytics = window.blogrGdprAcceptAnalytics;
window.blogrGdprHideAnalytics = window.blogrGdprDeclineAnalytics;

window.blogrGdprPostAnalyticsChoice = function (url) {
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = url;

    var csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value = '{{ csrf_token() }}';
    form.appendChild(csrf);

    var type = document.createElement('input');
    type.type = 'hidden';
    type.name = 'consent_type';
    type.value = 'analytics';
    form.appendChild(type);

    document.body.appendChild(form);
    form.submit();
};
</script>
@endpush
