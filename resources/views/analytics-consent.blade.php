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
@endphp

<div id="blogr-gdpr-analytics-consent" style="display: none; position: fixed; {{ $positionStyles }} z-index: 9998; padding: 16px; background: {{ $bgColor }}; color: {{ $textColor }}; box-shadow: 0 -2px 10px rgba(0,0,0,0.1); font-family: system-ui, -apple-system, sans-serif; font-size: 14px; line-height: 1.5;">
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
        <div style="display: flex; gap: 8px; margin-top: 10px;">
            <button onclick="blogrGdprAcceptAnalytics()" style="padding: 8px 20px; border: none; border-radius: 4px; cursor: pointer; background: var(--color-primary, #2563eb); color: #ffffff; font-size: 14px; font-weight: 500;">
                {{ __('blogr-gdpr::messages.accept') }}
            </button>
            <button onclick="blogrGdprDeclineAnalytics()" style="padding: 8px 20px; border: 1px solid {{ $isDark ? '#555' : '#ccc' }}; border-radius: 4px; cursor: pointer; background: transparent; color: inherit; font-size: 14px;">
                {{ __('blogr-gdpr::messages.decline') }}
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
window.blogrGdprAcceptAnalytics = function() {
    localStorage.setItem('blogr_gdpr_analytics', 'accepted');
    document.getElementById('blogr-gdpr-analytics-consent').style.display = 'none';
    blogrGdprShowAnalytics();
    fetch('{{ route('gdpr.consent') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ consent_type: 'analytics' })
    });
};

window.blogrGdprDeclineAnalytics = function() {
    localStorage.setItem('blogr_gdpr_analytics', 'declined');
    document.getElementById('blogr-gdpr-analytics-consent').style.display = 'none';
    blogrGdprHideAnalytics();
    fetch('{{ route('gdpr.withdraw') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ consent_type: 'analytics' })
    });
};
</script>
@endpush
