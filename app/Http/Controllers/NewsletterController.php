<?php

namespace App\Http\Controllers;

use App\Actions\SubscribeToNewsletter;
use App\Enums\Language;
use App\Http\Requests\SubscribeToNewsletterRequest;
use App\Models\NewsletterSubscriber;
use App\Support\Locales;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\App;

class NewsletterController extends Controller
{
    public function store(SubscribeToNewsletterRequest $request, SubscribeToNewsletter $subscribe): RedirectResponse
    {
        $validated = $request->validated();
        $subscriber = $subscribe->handle(
            $validated['email'],
            Language::from($validated['locale']),
        );

        return back()->with(
            'newsletter_status',
            __('newsletter.status.confirmation_sent', [], $subscriber->locale->value),
        );
    }

    public function showConfirmation(NewsletterSubscriber $newsletterSubscriber, string $token): View
    {
        $this->ensureValidActionToken($newsletterSubscriber, $token);

        if (! $newsletterSubscriber->isPending()) {
            return $this->statusView(
                $newsletterSubscriber,
                $newsletterSubscriber->isConfirmed() ? 'already_confirmed' : 'confirmation_not_applied',
            );
        }

        return $this->actionView($newsletterSubscriber, 'confirm');
    }

    public function confirm(NewsletterSubscriber $newsletterSubscriber, string $token): View
    {
        $this->ensureValidActionToken($newsletterSubscriber, $token);
        $confirmed = $newsletterSubscriber->confirm($token);
        $newsletterSubscriber->refresh();

        return $this->statusView(
            $newsletterSubscriber,
            $confirmed
                ? 'confirmed'
                : ($newsletterSubscriber->isConfirmed() ? 'already_confirmed' : 'confirmation_not_applied'),
        );
    }

    public function showUnsubscribe(NewsletterSubscriber $newsletterSubscriber, string $token): View
    {
        $this->ensureValidActionToken($newsletterSubscriber, $token);

        if ($newsletterSubscriber->isUnsubscribed()) {
            return $this->statusView($newsletterSubscriber, 'already_unsubscribed');
        }

        return $this->actionView($newsletterSubscriber, 'unsubscribe');
    }

    public function unsubscribe(NewsletterSubscriber $newsletterSubscriber, string $token): View
    {
        $this->ensureValidActionToken($newsletterSubscriber, $token);
        $unsubscribed = $newsletterSubscriber->unsubscribe($token);
        $newsletterSubscriber->refresh();

        return $this->statusView(
            $newsletterSubscriber,
            $unsubscribed ? 'unsubscribed' : 'already_unsubscribed',
        );
    }

    private function ensureValidActionToken(NewsletterSubscriber $newsletterSubscriber, string $token): void
    {
        abort_unless($newsletterSubscriber->hasValidActionToken($token), 404);
    }

    private function actionView(NewsletterSubscriber $newsletterSubscriber, string $action): View
    {
        $locale = $newsletterSubscriber->locale->value;
        App::setLocale($locale);

        return view('newsletter.action', [
            'title' => __('newsletter.status.title', [], $locale),
            'heading' => __("newsletter.action.{$action}_heading", [], $locale),
            'message' => __("newsletter.action.{$action}_message", [], $locale),
            'submit' => __("newsletter.action.{$action}_submit", [], $locale),
            'actionUrl' => request()->fullUrl(),
        ]);
    }

    private function statusView(NewsletterSubscriber $newsletterSubscriber, string $message): View
    {
        $locale = $newsletterSubscriber->locale->value;
        App::setLocale($locale);

        return view('newsletter.status', [
            'title' => __('newsletter.status.title', [], $locale),
            'message' => __('newsletter.status.'.$message, [], $locale),
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
