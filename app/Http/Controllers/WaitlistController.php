<?php

namespace App\Http\Controllers;

use App\Actions\SubscribeToWaitlist;
use App\Enums\Language;
use App\Http\Requests\SubscribeToWaitlistRequest;
use App\Models\WaitlistSubscriber;
use App\Support\Locales;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\App;

class WaitlistController extends Controller
{
    public function store(SubscribeToWaitlistRequest $request, SubscribeToWaitlist $subscribe): RedirectResponse
    {
        $validated = $request->validated();

        $subscriber = $subscribe->handle(
            $validated['email'],
            Language::from($validated['locale']),
        );

        return back()->with(
            'waitlist_status',
            __('waitlist.status.confirmation_sent', [], $subscriber->locale->value),
        );
    }

    public function showConfirmation(WaitlistSubscriber $waitlistSubscriber, string $token): View
    {
        $this->ensureValidActionToken($waitlistSubscriber, $token);

        if (! $waitlistSubscriber->isPending()) {
            return $this->statusView(
                $waitlistSubscriber,
                $waitlistSubscriber->isConfirmed() ? 'already_confirmed' : 'confirmation_not_applied',
            );
        }

        return $this->actionView($waitlistSubscriber, 'confirm');
    }

    public function confirm(WaitlistSubscriber $waitlistSubscriber, string $token): View
    {
        $this->ensureValidActionToken($waitlistSubscriber, $token);
        $confirmed = $waitlistSubscriber->confirm($token);
        $waitlistSubscriber->refresh();

        return $this->statusView(
            $waitlistSubscriber,
            $confirmed
                ? 'confirmed'
                : ($waitlistSubscriber->isConfirmed() ? 'already_confirmed' : 'confirmation_not_applied'),
        );
    }

    public function showUnsubscribe(WaitlistSubscriber $waitlistSubscriber, string $token): View
    {
        $this->ensureValidActionToken($waitlistSubscriber, $token);

        if ($waitlistSubscriber->isUnsubscribed()) {
            return $this->statusView($waitlistSubscriber, 'already_unsubscribed');
        }

        return $this->actionView($waitlistSubscriber, 'unsubscribe');
    }

    public function unsubscribe(WaitlistSubscriber $waitlistSubscriber, string $token): View
    {
        $this->ensureValidActionToken($waitlistSubscriber, $token);
        $unsubscribed = $waitlistSubscriber->unsubscribe($token);
        $waitlistSubscriber->refresh();

        return $this->statusView(
            $waitlistSubscriber,
            $unsubscribed ? 'unsubscribed' : 'already_unsubscribed',
        );
    }

    private function ensureValidActionToken(WaitlistSubscriber $waitlistSubscriber, string $token): void
    {
        abort_unless($waitlistSubscriber->hasValidActionToken($token), 404);
    }

    private function actionView(WaitlistSubscriber $waitlistSubscriber, string $action): View
    {
        $locale = $waitlistSubscriber->locale->value;

        App::setLocale($locale);

        return view('waitlist.action', [
            'title' => __('waitlist.status.title', [], $locale),
            'heading' => __("waitlist.action.{$action}_heading", [], $locale),
            'message' => __("waitlist.action.{$action}_message", [], $locale),
            'submit' => __("waitlist.action.{$action}_submit", [], $locale),
            'actionUrl' => request()->fullUrl(),
        ]);
    }

    private function statusView(WaitlistSubscriber $waitlistSubscriber, string $message): View
    {
        $locale = $waitlistSubscriber->locale->value;

        App::setLocale($locale);

        return view('waitlist.status', [
            'title' => __('waitlist.status.title', [], $locale),
            'message' => __('waitlist.status.'.$message, [], $locale),
            'homeUrl' => $this->homeUrl($locale),
        ]);
    }

    private function homeUrl(string $locale): string
    {
        if ($locale === Locales::fallback()) {
            return route('magazine.index');
        }

        return route('magazine.localized.index', ['locale' => $locale]);
    }
}
