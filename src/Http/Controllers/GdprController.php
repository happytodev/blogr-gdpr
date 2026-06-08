<?php

namespace Happytodev\BlogrGdpr\Http\Controllers;

use Happytodev\BlogrGdpr\Models\ConsentLog;
use Happytodev\BlogrGdpr\Services\ConsentService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class GdprController extends Controller
{
    public function __construct(
        protected ConsentService $consentService,
    ) {}

    public function storeConsent(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'consent_type' => 'required|string|in:cookies,analytics,contact',
            'consent_data' => 'nullable|array',
        ]);

        $this->consentService->giveConsent($validated['consent_type'], $validated['consent_data'] ?? []);

        return back()->with('success', __('blogr-gdpr::messages.consent_stored'));
    }

    public function withdrawConsent(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'consent_type' => 'required|string|in:cookies,analytics,contact',
        ]);

        $this->consentService->withdrawConsent($validated['consent_type']);

        return back()->with('success', __('blogr-gdpr::messages.consent_withdrawn'));
    }

    public function showDataExport(): \Illuminate\View\View
    {
        return view('blogr-gdpr::data-export');
    }

    public function requestDataExport(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $requestRecord = \Happytodev\BlogrGdpr\Models\GdprRequest::create([
            'email' => $validated['email'],
            'request_type' => 'export',
        ]);

        \Illuminate\Support\Facades\Notification::route('mail', config('blogr-gdpr.dpo.email'))
            ->notify(new \Happytodev\BlogrGdpr\Notifications\DataRequestNotification($requestRecord));

        return back()->with('success', __('blogr-gdpr::messages.export_requested'));
    }

    public function showDataErasure(): \Illuminate\View\View
    {
        return view('blogr-gdpr::data-erasure');
    }

    public function requestDataErasure(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'confirmation' => 'required|accepted',
        ]);

        $requestRecord = \Happytodev\BlogrGdpr\Models\GdprRequest::create([
            'email' => $validated['email'],
            'request_type' => 'erasure',
        ]);

        \Illuminate\Support\Facades\Notification::route('mail', config('blogr-gdpr.dpo.email'))
            ->notify(new \Happytodev\BlogrGdpr\Notifications\DataRequestNotification($requestRecord));

        return back()->with('success', __('blogr-gdpr::messages.erasure_requested'));
    }
}
