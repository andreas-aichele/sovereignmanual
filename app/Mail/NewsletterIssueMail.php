<?php

namespace App\Mail;

use App\Models\NewsletterDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class NewsletterIssueMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public NewsletterDelivery $newsletterDelivery) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->newsletterDelivery->issue->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.newsletter.issue',
            with: [
                'locale' => $this->newsletterDelivery->issue->locale->value,
                'issue' => $this->newsletterDelivery->issue,
                'unsubscribeUrl' => URL::signedRoute(
                    'newsletter.unsubscribe',
                    [
                        'newsletterSubscriber' => $this->newsletterDelivery->subscriber,
                        'token' => $this->newsletterDelivery->subscriber->action_token,
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
