<?php

namespace App\Mail;

use App\Models\WaitlistSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class ConfirmWaitlistSubscription extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public WaitlistSubscriber $waitlistSubscriber)
    {
        $this->afterCommit();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('waitlist.mail.subject', [], $this->waitlistSubscriber->locale->value),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.waitlist.confirmation',
            with: [
                'locale' => $this->waitlistSubscriber->locale->value,
                'confirmationUrl' => URL::temporarySignedRoute(
                    'waitlist.confirm',
                    now()->addHours(48),
                    [
                        'waitlistSubscriber' => $this->waitlistSubscriber,
                        'token' => $this->waitlistSubscriber->action_token,
                    ],
                ),
                'unsubscribeUrl' => URL::signedRoute(
                    'waitlist.unsubscribe',
                    [
                        'waitlistSubscriber' => $this->waitlistSubscriber,
                        'token' => $this->waitlistSubscriber->action_token,
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
