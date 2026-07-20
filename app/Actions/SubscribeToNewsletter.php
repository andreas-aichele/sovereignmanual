<?php

namespace App\Actions;

use App\Enums\Language;
use App\Enums\NewsletterSubscriberStatus;
use App\Mail\ConfirmNewsletterSubscription;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SubscribeToNewsletter
{
    public function handle(string $email, Language $locale): NewsletterSubscriber
    {
        return DB::transaction(function () use ($email, $locale): NewsletterSubscriber {
            $subscriber = NewsletterSubscriber::query()
                ->where('email', $email)
                ->lockForUpdate()
                ->first();

            if ($subscriber?->isConfirmed()) {
                return $subscriber;
            }

            $subscriber ??= new NewsletterSubscriber;

            $subscriber->fill([
                'email' => $email,
                'locale' => $locale,
                'status' => NewsletterSubscriberStatus::Pending,
                'action_token' => Str::random(64),
                'consented_at' => now(),
                'confirmed_at' => null,
                'unsubscribed_at' => null,
            ])->save();

            Mail::to($subscriber->email)
                ->locale($subscriber->locale->value)
                ->queue(new ConfirmNewsletterSubscription($subscriber));

            return $subscriber;
        });
    }
}
