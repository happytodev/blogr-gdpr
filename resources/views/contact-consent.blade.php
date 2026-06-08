<div class="mt-4">
    <label class="flex items-start gap-3 cursor-pointer text-sm" style="color: inherit;">
        <input type="checkbox" x-model="gdprConsent" class="mt-0.5 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
        <span>
            {{ __('blogr-gdpr::messages.contact_consent_text') }}
            @if(config('blogr-gdpr.privacy_policy.auto_create'))
                <a href="{{ route('cms.page.show', ['locale' => app()->getLocale(), 'slug' => 'privacy-policy']) }}" class="underline" style="color: var(--color-primary, #2563eb);" target="_blank">
                    {{ __('blogr-gdpr::messages.privacy_policy') }}
                </a>
            @endif
        </span>
    </label>
    <div x-show="!gdprConsent && consentTouched" class="text-red-600 dark:text-red-400 text-xs mt-1">
        {{ __('blogr-gdpr::messages.contact_consent_required') }}
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', function() {
    var origContactForm = Alpine.data('contactForm');
    Alpine.data('contactForm', function(config) {
        var base = origContactForm.call(this, config);
        var origSubmit = base.submit;
        return Object.assign({}, base, {
            gdprConsent: false,
            consentTouched: false,
            submit: function() {
                if (!this.gdprConsent) {
                    this.consentTouched = true;
                    return;
                }
                return origSubmit.call(this);
            },
        });
    });
});
</script>
@endpush
