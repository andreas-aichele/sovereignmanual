<?php

namespace App\Mail;

use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class ConfirmNewsletterSubscription extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public NewsletterSubscriber $newsletterSubscriber)
    {
        $this->afterCommit();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('newsletter.mail.subject', [], $this->newsletterSubscriber->locale->value),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.newsletter.confirmation',
            with: [
                'locale' => $this->newsletterSubscriber->locale->value,
                'confirmationUrl' => URL::temporarySignedRoute(
                    'newsletter.confirm',
                    now()->addHours(48),
                    [
                        'newsletterSubscriber' => $this->newsletterSubscriber,
                        'token' => $this->newsletterSubscriber->action_token,
                    ],
                ),
                'unsubscribeUrl' => URL::signedRoute(
                    'newsletter.unsubscribe',
                    [
                        'newsletterSubscriber' => $this->newsletterSubscriber,
                        'token' => $this->newsletterSubscriber->action_token,
                    ],
                ),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
