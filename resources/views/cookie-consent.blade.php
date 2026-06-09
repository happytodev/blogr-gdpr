@php
    $position = config('blogr-gdpr.cookie_consent.position', 'bottom');
    $theme = config('blogr-gdpr.cookie_consent.theme', 'dark');
    $isDark = $theme === 'dark';
    $bgColor = $isDark ? '#1a1a2e' : '#ffffff';
    $textColor = $isDark ? '#e0e0e0' : '#333333';
    $borderColor = $isDark ? '#333' : '#e0e0e0';
    $categories = config('blogr-gdpr.cookie_consent.categories', []);
    $showAnalyticsToggle = config('blogr-gdpr.analytics_consent.enabled') && filled(config('blogr.analytics.provider'));
    $positionStyles = $position === 'bottom'
        ? 'bottom: 0; left: 0; right: 0; border-top: 1px solid ' . $borderColor . ';'
        : 'top: 0; left: 0; right: 0; border-bottom: 1px solid ' . $borderColor . ';';
@endphp

<div id="blogr-gdpr-cookie-consent" style="display: none; position: fixed; {{ $positionStyles }} z-index: 9999; padding: 16px; background: {{ $bgColor }}; color: {{ $textColor }}; box-shadow: 0 -2px 10px rgba(0,0,0,0.1); font-family: system-ui, -apple-system, sans-serif; font-size: 14px; line-height: 1.5;">
    <div style="max-width: 1200px; margin: 0 auto; display: flex; flex-wrap: wrap; align-items: center; gap: 12px;">
        <div style="flex: 1; min-width: 200px;">
            <p style="margin: 0;">
                {{ __('blogr-gdpr::messages.cookie_banner_text') }}
                @if(config('blogr-gdpr.cookie_consent.info_url'))
                    <a href="{{ config('blogr-gdpr.cookie_consent.info_url') }}" style="color: inherit; text-decoration: underline;">
                        {{ __('blogr-gdpr::messages.learn_more') }}
                    </a>
                @endif
            </p>
        </div>
        <div style="display: flex; gap: 8px; flex-shrink: 0;">
            <button onclick="blogrGdprCustomizeCookies()" style="padding: 8px 20px; border: 1px solid {{ $isDark ? '#555' : '#ccc' }}; border-radius: 4px; cursor: pointer; background: transparent; color: inherit; font-size: 14px;">
                {{ __('blogr-gdpr::messages.customize') }}
            </button>
            <button onclick="blogrGdprAcceptCookies()" style="padding: 8px 20px; border: none; border-radius: 4px; cursor: pointer; background: var(--color-primary, #2563eb); color: #ffffff; font-size: 14px; font-weight: 500;">
                {{ __('blogr-gdpr::messages.accept_all') }}
            </button>
        </div>
    </div>
</div>

{{-- Customize Modal --}}
<div id="blogr-gdpr-cookie-modal" style="display: none; position: fixed; inset: 0; z-index: 10000; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; font-family: system-ui, -apple-system, sans-serif;">
    <div style="background: {{ $bgColor }}; color: {{ $textColor }}; border-radius: 12px; padding: 24px; max-width: 480px; width: 90%; max-height: 80vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
        <h3 style="margin: 0 0 16px 0; font-size: 18px; font-weight: 600;">{{ __('blogr-gdpr::messages.cookie_preferences') }}</h3>
        <p style="margin: 0 0 16px 0; font-size: 13px; color: {{ $isDark ? '#aaa' : '#666' }};">
            {{ __('blogr-gdpr::messages.cookie_preferences_intro') }}
        </p>

        @foreach($categories as $key => $category)
        <div style="margin-bottom: 12px; padding: 12px; border-radius: 8px; border: 1px solid {{ $borderColor }};">
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                <input type="checkbox" class="blogr-gdpr-cookie-category" data-category="{{ $key }}"
                    {{ ($category['required'] ?? false) ? 'checked disabled' : '' }}
                    {{ ($category['default'] ?? false) ? 'checked' : '' }}
                    style="width: 16px; height: 16px;">
                <div>
                    <div style="font-weight: 600; font-size: 14px;">{{ $category['label'] ?? $key }}</div>
                    <div style="font-size: 12px; color: {{ $isDark ? '#aaa' : '#666' }};">{{ $category['description'] ?? '' }}</div>
                </div>
            </label>
        </div>
        @endforeach

        @if($showAnalyticsToggle)
        <div style="margin-bottom: 12px; padding: 12px; border-radius: 8px; border: 1px solid {{ $borderColor }};">
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                <input type="checkbox" id="blogr-gdpr-analytics-toggle" style="width: 16px; height: 16px;">
                <div>
                    <div style="font-weight: 600; font-size: 14px;">{{ __('blogr-gdpr::messages.analytics_tracking') }}</div>
                    <div style="font-size: 12px; color: {{ $isDark ? '#aaa' : '#666' }};">{{ __('blogr-gdpr::messages.analytics_description') }}</div>
                </div>
            </label>
        </div>
        @endif

        <div style="display: flex; gap: 8px; justify-content: flex-end; margin-top: 16px; border-top: 1px solid {{ $borderColor }}; padding-top: 16px;">
            <button onclick="blogrGdprCloseModal()" style="padding: 8px 20px; border: 1px solid {{ $isDark ? '#555' : '#ccc' }}; border-radius: 4px; cursor: pointer; background: transparent; color: inherit; font-size: 14px;">
                {{ __('Cancel') }}
            </button>
            <button onclick="blogrGdprSavePreferences()" style="padding: 8px 20px; border: none; border-radius: 4px; cursor: pointer; background: var(--color-primary, #2563eb); color: #ffffff; font-size: 14px; font-weight: 500;">
                {{ __('blogr-gdpr::messages.save_preferences') }}
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    if (localStorage.getItem('blogr_gdpr_cookies') !== null) {
        return;
    }
    document.getElementById('blogr-gdpr-cookie-consent').style.display = 'block';
})();

window.blogrGdprGetCookieCategories = function() {
    var saved = localStorage.getItem('blogr_gdpr_cookie_categories');
    if (saved) {
        return JSON.parse(saved);
    }
    var categories = {};
    document.querySelectorAll('.blogr-gdpr-cookie-category').forEach(function(cb) {
        categories[cb.dataset.category] = cb.checked;
    });
    return categories;
};

window.blogrGdprLoadPreferences = function() {
    var saved = localStorage.getItem('blogr_gdpr_cookie_categories');
    if (saved) {
        var categories = JSON.parse(saved);
        document.querySelectorAll('.blogr-gdpr-cookie-category').forEach(function(cb) {
            if (!cb.disabled) {
                cb.checked = categories[cb.dataset.category] === true;
            }
        });
    }
    var analytics = localStorage.getItem('blogr_gdpr_analytics');
    var toggle = document.getElementById('blogr-gdpr-analytics-toggle');
    if (toggle) {
        toggle.checked = analytics === 'accepted';
    }
};

window.blogrGdprOpenPreferences = function() {
    blogrGdprLoadPreferences();
    document.getElementById('blogr-gdpr-cookie-modal').style.display = 'flex';
};

window.blogrGdprAcceptCookies = function() {
    var categories = {};
    document.querySelectorAll('.blogr-gdpr-cookie-category').forEach(function(cb) {
        categories[cb.dataset.category] = true;
    });
    localStorage.setItem('blogr_gdpr_cookie_categories', JSON.stringify(categories));
    localStorage.setItem('blogr_gdpr_cookies', 'accepted');
    document.getElementById('blogr-gdpr-cookie-consent').style.display = 'none';
    blogrGdprCloseModal();
    fetch('{{ route('gdpr.consent') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ consent_type: 'cookies', consent_data: { categories: categories } })
    });

    var toggle = document.getElementById('blogr-gdpr-analytics-toggle');
    if (toggle) {
        localStorage.setItem('blogr_gdpr_analytics', 'accepted');
        fetch('{{ route('gdpr.consent') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ consent_type: 'analytics' })
        });
    }
};

window.blogrGdprDeclineCookies = function() {
    var categories = {};
    document.querySelectorAll('.blogr-gdpr-cookie-category').forEach(function(cb) {
        categories[cb.dataset.category] = cb.checked && cb.disabled;
    });
    localStorage.setItem('blogr_gdpr_cookie_categories', JSON.stringify(categories));
    localStorage.setItem('blogr_gdpr_cookies', 'declined');
    document.getElementById('blogr-gdpr-cookie-consent').style.display = 'none';
    blogrGdprCloseModal();
    fetch('{{ route('gdpr.withdraw') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ consent_type: 'cookies' })
    });
};

window.blogrGdprCustomizeCookies = function() {
    blogrGdprLoadPreferences();
    document.getElementById('blogr-gdpr-cookie-modal').style.display = 'flex';
};

window.blogrGdprCloseModal = function() {
    document.getElementById('blogr-gdpr-cookie-modal').style.display = 'none';
};

window.blogrGdprSavePreferences = function() {
    var categories = {};
    document.querySelectorAll('.blogr-gdpr-cookie-category').forEach(function(cb) {
        categories[cb.dataset.category] = cb.checked;
    });
    localStorage.setItem('blogr_gdpr_cookie_categories', JSON.stringify(categories));
    localStorage.setItem('blogr_gdpr_cookies', 'customized');
    document.getElementById('blogr-gdpr-cookie-consent').style.display = 'none';
    blogrGdprCloseModal();
    fetch('{{ route('gdpr.consent') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ consent_type: 'cookies', consent_data: { categories: categories } })
    });

    var toggle = document.getElementById('blogr-gdpr-analytics-toggle');
    if (toggle) {
        var analyticsAccepted = toggle.checked;
        localStorage.setItem('blogr_gdpr_analytics', analyticsAccepted ? 'accepted' : 'declined');
        if (analyticsAccepted) {
            fetch('{{ route('gdpr.consent') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ consent_type: 'analytics' })
            });
        } else {
            fetch('{{ route('gdpr.withdraw') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ consent_type: 'analytics' })
            });
        }
    }
};
</script>
@endpush
