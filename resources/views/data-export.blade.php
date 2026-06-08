@extends('blogr::layouts.blog')

@section('content')
<div style="max-width: 800px; margin: 0 auto; padding: 40px 20px;">
    <h1 style="font-size: 28px; margin-bottom: 24px;">{{ __('blogr-gdpr::messages.data_export_title') }}</h1>

    @if(session('success'))
        <div style="padding: 12px 16px; background: #d1fae5; color: #065f46; border-radius: 6px; margin-bottom: 24px;">
            {{ session('success') }}
        </div>
    @endif

    <p style="margin-bottom: 24px; line-height: 1.7; color: #555;">
        {{ __('blogr-gdpr::messages.data_export_intro') }}
    </p>

    <form method="POST" action="{{ route('gdpr.data-export.request') }}" style="max-width: 400px;">
        @csrf
        <div style="margin-bottom: 16px;">
            <label for="email" style="display: block; margin-bottom: 6px; font-weight: 500; font-size: 14px;">
                {{ __('blogr-gdpr::messages.email_address') }}
            </label>
            <input type="email" name="email" id="email" required
                   style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;">
            @error('email')
                <div style="color: #dc2626; font-size: 13px; margin-top: 4px;">{{ $message }}</div>
            @enderror
        </div>
        <button type="submit" style="padding: 10px 24px; background: var(--color-primary, #2563eb); color: #ffffff; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500;">
            {{ __('blogr-gdpr::messages.request_export') }}
        </button>
    </form>
</div>
@endsection
