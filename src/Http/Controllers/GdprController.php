<?php

namespace Happytodev\BlogrGdpr\Http\Controllers;

use Happytodev\BlogrGdpr\Models\GdprRequest;
use Happytodev\BlogrGdpr\Notifications\DataRequestNotification;
use Happytodev\BlogrGdpr\Services\ConsentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class GdprController extends Controller
{
    public function __construct(
        protected ConsentService $consentService,
    ) {}

    private function applySecurityHeaders(Response|RedirectResponse|View $response): mixed
    {
        if ($response instanceof View) {
            return $response;
        }

        return $response->withHeaders([
            'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';",
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
        ]);
    }

    public function storeConsent(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'consent_type' => 'required|string|in:cookies,analytics,contact',
            'consent_data' => 'nullable|array',
        ]);

        $this->consentService->giveConsent($validated['consent_type'], $validated['consent_data'] ?? []);

        return $this->applySecurityHeaders(back()->with('success', __('blogr-gdpr::messages.consent_stored')));
    }

    public function withdrawConsent(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'consent_type' => 'required|string|in:cookies,analytics,contact',
        ]);

        $this->consentService->withdrawConsent($validated['consent_type']);

        return $this->applySecurityHeaders(back()->with('success', __('blogr-gdpr::messages.consent_withdrawn')));
    }

    public function showDataExport(): View
    {
        return view('blogr-gdpr::data-export');
    }

    public function requestDataExport(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $requestRecord = GdprRequest::create([
            'email' => $validated['email'],
            'request_type' => 'export',
        ]);

        Notification::route('mail', config('blogr-gdpr.dpo.email'))
            ->notify(new DataRequestNotification($requestRecord));

        return $this->applySecurityHeaders(back()->with('success', __('blogr-gdpr::messages.export_requested')));
    }

    public function showDataErasure(): View
    {
        return view('blogr-gdpr::data-erasure');
    }

    public function requestDataErasure(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'confirmation' => 'required|accepted',
        ]);

        $requestRecord = GdprRequest::create([
            'email' => $validated['email'],
            'request_type' => 'erasure',
        ]);

        Notification::route('mail', config('blogr-gdpr.dpo.email'))
            ->notify(new DataRequestNotification($requestRecord));

        return $this->applySecurityHeaders(back()->with('success', __('blogr-gdpr::messages.erasure_requested')));
    }
}
