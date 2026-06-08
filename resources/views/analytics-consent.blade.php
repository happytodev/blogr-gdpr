<div id="blogr-gdpr-analytics-consent" style="display: none; margin: 12px 0;">
    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 8px;">
        <span style="font-size: 13px; color: #666;">
            {{ __('blogr-gdpr::messages.analytics_consent_text') }}
        </span>
        <button onclick="blogrGdprAcceptAnalytics()" style="padding: 6px 16px; border: none; border-radius: 4px; cursor: pointer; background: var(--color-primary, #2563eb); color: #ffffff; font-size: 13px;">
            {{ __('blogr-gdpr::messages.accept') }}
        </button>
        <button onclick="blogrGdprDeclineAnalytics()" style="padding: 6px 16px; border: 1px solid #ccc; border-radius: 4px; cursor: pointer; background: transparent; color: #666; font-size: 13px;">
            {{ __('blogr-gdpr::messages.decline') }}
        </button>
    </div>
</div>

@push('scripts')
<script>
(function() {
    var consent = localStorage.getItem('blogr_gdpr_analytics');
    if (consent === 'accepted') {
        blogrGdprShowAnalytics();
    } else if (consent === null) {
        document.getElementById('blogr-gdpr-analytics-consent').style.display = 'block';
    }
})();

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
