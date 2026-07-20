<?php

namespace App\Actions;

use App\Enums\Language;
use App\Enums\WaitlistSubscriberStatus;
use App\Mail\ConfirmWaitlistSubscription;
use App\Models\WaitlistSubscriber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SubscribeToWaitlist
{
    public function handle(string $email, Language $locale): WaitlistSubscriber
    {
        return DB::transaction(function () use ($email, $locale): WaitlistSubscriber {
            $subscriber = WaitlistSubscriber::query()
                ->where('email', $email)
                ->lockForUpdate()
                ->first();

            if ($subscriber?->isConfirmed()) {
                return $subscriber;
            }

            $subscriber ??= new WaitlistSubscriber;

            $subscriber->fill([
                'email' => $email,
                'locale' => $locale,
                'status' => WaitlistSubscriberStatus::Pending,
                'action_token' => Str::random(64),
                'consented_at' => now(),
                'confirmed_at' => null,
                'unsubscribed_at' => null,
            ])->save();

            Mail::to($subscriber->email)
                ->locale($subscriber->locale->value)
                ->queue(new ConfirmWaitlistSubscription($subscriber));

            return $subscriber;
        });
    }
}
