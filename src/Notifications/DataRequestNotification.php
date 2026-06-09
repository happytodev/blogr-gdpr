<?php

namespace Happytodev\BlogrGdpr\Notifications;

use Happytodev\BlogrGdpr\Models\GdprRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DataRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected GdprRequest $request,
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $type = $this->request->request_type === 'export'
            ? __('blogr-gdpr::messages.export_request')
            : __('blogr-gdpr::messages.erasure_request');

        return (new MailMessage)
            ->subject(__('blogr-gdpr::messages.data_request_subject', ['type' => $type]))
            ->line(__('blogr-gdpr::messages.data_request_intro'))
            ->line(__('blogr-gdpr::messages.data_request_email', ['email' => $this->request->email]))
            ->line(__('blogr-gdpr::messages.data_request_type', ['type' => $type]))
            ->action(__('blogr-gdpr::messages.data_request_action'), url('/admin/blogr-gdpr-requests'));
    }
}
