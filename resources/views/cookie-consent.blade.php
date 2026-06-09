@php
    $position = config('blogr-gdpr.cookie_consent.position', 'bottom');
    $theme = config('blogr-gdpr.cookie_consent.theme', 'dark');
    $isDark = $theme === 'dark';
    $bgColor = $isDark ? '#1a1a2e' : '#ffffff';
    $textColor = $isDark ? '#e0e0e0' : '#333333';
    $borderColor = $isDark ? '#333' : '#e0e0e0';
    $categories = config('blogr-gdpr.cookie_consent.categories', []);
    $provider = config('blogr.analytics.provider');
    $providerLabel = $provider ? __('blogr-gdpr::messages.providers.' . $provider . '.label') : null;
    $providerPro = $provider ? __('blogr-gdpr::messages.providers.' . $provider . '.pro') : null;
    $providerCon = $provider ? __('blogr-gdpr::messages.providers.' . $provider . '.con') : null;
    $showAnalyticsProvider = $provider && config('blogr-gdpr.analytics_consent.enabled');
    $siteName = config('app.name', 'This site');
    $positionStyles = $position === 'bottom'
        ? 'bottom: 0; left: 0; right: 0; border-top: 1px solid ' . $borderColor . ';'
        : 'top: 0; left: 0; right: 0; border-bottom: 1px solid ' . $borderColor . ';';
@endphp

<div id="blogr-gdpr-cookie-consent" style="display: none; position: fixed; {{ $positionStyles }} z-index: 9999; padding: 20px; background: {{ $bgColor }}; color: {{ $textColor }}; box-shadow: 0 -2px 10px rgba(0,0,0,0.1); font-family: system-ui, -apple-system, sans-serif; font-size: 14px; line-height: 1.6;">
    <div style="max-width: 1200px; margin: 0 auto;">
        <div style="display: flex; align-items: flex-start; gap: 12px;">
            <div style="flex-shrink: 0; width: 48px; height: 48px;">
                <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="50" cy="50" r="45" fill="#e8b84b" stroke="#c99429" stroke-width="3"/>
                    <circle cx="35" cy="35" r="6" fill="#6b4423"/>
                    <circle cx="60" cy="30" r="5" fill="#6b4423"/>
                    <circle cx="45" cy="55" r="7" fill="#6b4423"/>
                    <circle cx="65" cy="50" r="5" fill="#6b4423"/>
                    <circle cx="30" cy="60" r="4" fill="#6b4423"/>
                    <circle cx="55" cy="65" r="4" fill="#6b4423"/>
                    <circle cx="40" cy="42" r="3" fill="#6b4423"/>
                    <path d="M20 50 Q15 30 35 20" fill="none" stroke="#c99429" stroke-width="2" opacity="0.5"/>
                    <path d="M65 75 Q80 65 75 45" fill="none" stroke="#c99429" stroke-width="2" opacity="0.5"/>
                </svg>
            </div>
            <div style="flex: 1; min-width: 0;">
                <p style="margin: 0 0 4px 0; font-weight: 600; font-size: 15px;">
                    {{ __('blogr-gdpr::messages.banner_title', ['site' => $siteName]) }}
                </p>
                <p style="margin: 0 0 8px 0; font-size: 13px; color: {{ $isDark ? '#aaa' : '#666' }};">
                    {{ __('blogr-gdpr::messages.banner_intro') }}
                </p>

                <div style="margin: 12px 0; padding: 12px; border-radius: 8px; border: 1px solid {{ $borderColor }}; background: {{ $isDark ? 'rgba(255,255,255,0.03)' : 'rgba(0,0,0,0.02)' }};">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                        <span style="font-size: 16px;">🍪</span>
                        <span style="font-weight: 500; font-size: 13px;">{{ __('blogr-gdpr::messages.essential_cookies') }}</span>
                        <span style="font-size: 11px; color: #22c55e; margin-left: auto;">{{ __('blogr-gdpr::messages.always_active') }}</span>
                    </div>
                    <div style="font-size: 12px; color: {{ $isDark ? '#888' : '#999' }}; margin-left: 32px;">
                        {{ __('blogr-gdpr::messages.essential_description') }}
                    </div>

                    @if($showAnalyticsProvider)
                    <div style="display: flex; align-items: center; gap: 8px; margin-top: 10px;">
                        <span style="font-size: 16px;">📊</span>
                        <span style="font-weight: 500; font-size: 13px;">{{ $providerLabel }}</span>
                    </div>
                    <div style="font-size: 12px; color: {{ $isDark ? '#888' : '#999' }}; margin-left: 32px;">
                        <span style="color: #22c55e;">&#10003;</span> {{ $providerPro }} &nbsp;
                        <span style="color: #f97316;">&#9888;</span> {{ $providerCon }}
                    </div>
                    @else
                    <div style="display: flex; align-items: center; gap: 8px; margin-top: 10px;">
                        <span style="font-size: 16px;">📈</span>
                        <span style="font-weight: 500; font-size: 13px;">{{ $categories['analytics']['label'] ?? 'Analytics' }}</span>
                    </div>
                    <div style="font-size: 12px; color: {{ $isDark ? '#888' : '#999' }}; margin-left: 32px;">
                        {{ __('blogr-gdpr::messages.analytics_description') }}
                    </div>
                    @endif

                    <div style="display: flex; align-items: center; gap: 8px; margin-top: 10px;">
                        <span style="font-size: 16px;">📢</span>
                        <span style="font-weight: 500; font-size: 13px;">{{ $categories['marketing']['label'] ?? 'Marketing' }}</span>
                    </div>
                    <div style="font-size: 12px; color: {{ $isDark ? '#888' : '#999' }}; margin-left: 32px;">
                        {{ __('blogr-gdpr::messages.marketing_description') }}
                    </div>
                </div>

                <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px;">
                    <button onclick="blogrGdprAcceptCookies()" style="padding: 8px 20px; border: none; border-radius: 6px; cursor: pointer; background: var(--color-primary, #2563eb); color: #ffffff; font-size: 13px; font-weight: 600;">
                        {{ __('blogr-gdpr::messages.accept_all') }}
                    </button>
                    <button onclick="blogrGdprAcceptEssentialOnly()" style="padding: 8px 20px; border: 1px solid {{ $isDark ? '#555' : '#ccc' }}; border-radius: 6px; cursor: pointer; background: transparent; color: inherit; font-size: 13px;">
                        {{ __('blogr-gdpr::messages.essential_only') }}
                    </button>
                    <button onclick="blogrGdprCustomizeCookies()" style="padding: 8px 20px; border: 1px solid {{ $isDark ? '#555' : '#ccc' }}; border-radius: 6px; cursor: pointer; background: transparent; color: inherit; font-size: 13px;">
                        {{ __('blogr-gdpr::messages.customize') }}
                    </button>
                </div>

                <p style="margin: 10px 0 0 0; font-size: 11px; color: {{ $isDark ? '#666' : '#aaa' }};">
                    @if(config('blogr-gdpr.privacy_policy.auto_create'))
                    <a href="{{ route('cms.page.show', ['locale' => app()->getLocale(), 'slug' => 'privacy-policy']) }}" style="color: inherit; text-decoration: underline;">{{ __('blogr-gdpr::messages.privacy_policy') }}</a>
                    @endif
                </p>
            </div>
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
                    <div style="font-size: 12px; color: {{ $isDark ? '#aaa' : '#666' }};">
                        {{ $category['description'] ?? '' }}
                        @if($key === 'analytics' && $showAnalyticsProvider)
                        <br>
                        <span style="font-weight: 500;">{{ $providerLabel }}</span><br>
                        <span style="color: #22c55e;">&#10003;</span> {{ $providerPro }}<br>
                        <span style="color: #f97316;">&#9888;</span> {{ $providerCon }}
                    @endif
                    </div>
                </div>
            </label>
        </div>
        @endforeach

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
        var analytics = localStorage.getItem('blogr_gdpr_analytics');
        if (analytics === 'accepted') {
            if (typeof blogrGdprShowAnalytics === 'function') blogrGdprShowAnalytics();
        } else if (analytics === null) {
            var gate = document.getElementById('blogr-gdpr-analytics-consent');
            if (gate) gate.style.display = 'block';
        }
        return;
    }
    document.getElementById('blogr-gdpr-cookie-consent').style.display = 'block';
    var analyticsGate = document.getElementById('blogr-gdpr-analytics-consent');
    if (analyticsGate) {
        analyticsGate.style.display = 'none';
    }
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
    var analyticsCb = blogrGdprGetAnalyticsCheckbox();
    if (analyticsCb && !analyticsCb.disabled) {
        analyticsCb.checked = analytics === 'accepted';
    }
};

window.blogrGdprOpenPreferences = function() {
    blogrGdprLoadPreferences();
    document.getElementById('blogr-gdpr-cookie-modal').style.display = 'flex';
};

window.blogrGdprGetAnalyticsCheckbox = function() {
    return document.querySelector('.blogr-gdpr-cookie-category[data-category="analytics"]');
};

window.blogrGdprSendAnalyticsConsent = function(accepted) {
    localStorage.setItem('blogr_gdpr_analytics', accepted ? 'accepted' : 'declined');
    var url = accepted ? '{{ route('gdpr.consent') }}' : '{{ route('gdpr.withdraw') }}';
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ consent_type: 'analytics' })
    });
};

window.blogrGdprSendCookiesConsent = function(categories) {
    fetch('{{ route('gdpr.consent') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ consent_type: 'cookies', consent_data: { categories: categories } })
    });
};

window.blogrGdprHideAnalyticsGate = function() {
    var gate = document.getElementById('blogr-gdpr-analytics-consent');
    if (gate) {
        gate.style.display = 'none';
    }
};

window.blogrGdprHideBanner = function() {
    document.getElementById('blogr-gdpr-cookie-consent').style.display = 'none';
    blogrGdprHideAnalyticsGate();
    blogrGdprCloseModal();
};

window.blogrGdprAcceptCookies = function() {
    var categories = {};
    document.querySelectorAll('.blogr-gdpr-cookie-category').forEach(function(cb) {
        categories[cb.dataset.category] = true;
    });
    localStorage.setItem('blogr_gdpr_cookie_categories', JSON.stringify(categories));
    localStorage.setItem('blogr_gdpr_cookies', 'accepted');
    blogrGdprHideBanner();
    blogrGdprSendCookiesConsent(categories);

    if (blogrGdprGetAnalyticsCheckbox()) {
        blogrGdprSendAnalyticsConsent(true);
    }
};

window.blogrGdprAcceptEssentialOnly = function() {
    var categories = {};
    document.querySelectorAll('.blogr-gdpr-cookie-category').forEach(function(cb) {
        categories[cb.dataset.category] = !!cb.disabled;
    });
    localStorage.setItem('blogr_gdpr_cookie_categories', JSON.stringify(categories));
    localStorage.setItem('blogr_gdpr_cookies', 'accepted');
    localStorage.setItem('blogr_gdpr_analytics', 'declined');
    blogrGdprHideBanner();
    blogrGdprSendCookiesConsent(categories);

    if (blogrGdprGetAnalyticsCheckbox()) {
        fetch('{{ route('gdpr.withdraw') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ consent_type: 'analytics' })
        });
    }
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
    blogrGdprHideBanner();
    blogrGdprSendCookiesConsent(categories);

    var analyticsCb = blogrGdprGetAnalyticsCheckbox();
    if (analyticsCb) {
        blogrGdprSendAnalyticsConsent(analyticsCb.checked);
    }
};
</script>
@endpush
