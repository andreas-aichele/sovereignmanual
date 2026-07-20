<?php

use App\Enums\Language;
use App\Enums\NewsletterSubscriberStatus;
use App\Mail\ConfirmNewsletterSubscription;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

test('submitting the newsletter form creates a pending subscription and queues confirmation', function () {
    Mail::fake();

    $this->from(route('magazine.index'))
        ->post(route('newsletter.store'), [
            'email' => ' Reader@Example.com ',
            'locale' => Language::German->value,
            'consent' => 'on',
        ])
        ->assertRedirect(route('magazine.index'))
        ->assertSessionHas('newsletter_status');

    $subscriber = NewsletterSubscriber::query()->sole();

    expect($subscriber->email)->toBe('reader@example.com')
        ->and($subscriber->locale)->toBe(Language::German)
        ->and($subscriber->status)->toBe(NewsletterSubscriberStatus::Pending)
        ->and($subscriber->action_token)->not->toBeNull()
        ->and($subscriber->consented_at)->not->toBeNull()
        ->and($subscriber->confirmed_at)->toBeNull()
        ->and($subscriber->unsubscribed_at)->toBeNull();

    Mail::assertQueued(ConfirmNewsletterSubscription::class, function (ConfirmNewsletterSubscription $mail) use ($subscriber): bool {
        return $mail->newsletterSubscriber->is($subscriber)
            && $mail->hasTo($subscriber->email);
    });
});

test('newsletter submissions require consent and a supported locale', function () {
    Mail::fake();

    $this->from(route('magazine.index'))
        ->post(route('newsletter.store'), [
            'email' => 'reader@example.com',
            'locale' => 'fr',
        ])
        ->assertRedirect(route('magazine.index'))
        ->assertSessionHasErrors(['locale', 'consent']);

    expect(NewsletterSubscriber::query()->count())->toBe(0);

    Mail::assertNothingQueued();
});

test('a confirmed newsletter subscription is not re-confirmed or emailed again', function () {
    Mail::fake();

    $subscriber = NewsletterSubscriber::factory()->confirmed()->create([
        'email' => 'reader@example.com',
        'locale' => Language::German,
    ]);
    $consentedAt = $subscriber->consented_at;

    $this->from(route('magazine.index'))
        ->post(route('newsletter.store'), [
            'email' => 'reader@example.com',
            'locale' => Language::English->value,
            'consent' => 'on',
        ])
        ->assertRedirect(route('magazine.index'));

    expect($subscriber->fresh()->status)->toBe(NewsletterSubscriberStatus::Confirmed)
        ->and($subscriber->fresh()->locale)->toBe(Language::German)
        ->and($subscriber->fresh()->consented_at->equalTo($consentedAt))->toBeTrue();

    Mail::assertNothingQueued();
});

test('an unsubscribed address can start a new newsletter confirmation flow', function () {
    Mail::fake();

    $subscriber = NewsletterSubscriber::factory()->unsubscribed()->create([
        'email' => 'reader@example.com',
    ]);

    $this->from(route('magazine.index'))
        ->post(route('newsletter.store'), [
            'email' => 'reader@example.com',
            'locale' => Language::German->value,
            'consent' => 'on',
        ])
        ->assertRedirect(route('magazine.index'));

    expect($subscriber->fresh()->status)->toBe(NewsletterSubscriberStatus::Pending)
        ->and($subscriber->fresh()->locale)->toBe(Language::German)
        ->and($subscriber->fresh()->confirmed_at)->toBeNull()
        ->and($subscriber->fresh()->unsubscribed_at)->toBeNull();

    Mail::assertQueued(ConfirmNewsletterSubscription::class);
});

test('the confirmation email is localized and contains signed confirmation and unsubscribe links', function () {
    $subscriber = NewsletterSubscriber::factory()->create([
        'locale' => Language::German,
    ]);

    (new ConfirmNewsletterSubscription($subscriber))
        ->assertSeeInHtml('Anmeldung bestätigen')
        ->assertSeeInHtml('newsletter/confirm')
        ->assertSeeInHtml('newsletter/unsubscribe')
        ->assertSeeInHtml('signature=');
});

test('a temporary signed link confirms a pending newsletter subscription', function () {
    $subscriber = NewsletterSubscriber::factory()->create();
    $confirmationUrl = newsletterConfirmationUrl($subscriber);

    $this->get($confirmationUrl)
        ->assertSuccessful()
        ->assertViewIs('newsletter.action')
        ->assertSee('Confirm newsletter subscription');

    expect($subscriber->fresh()->status)->toBe(NewsletterSubscriberStatus::Pending);

    $this->post($confirmationUrl)
        ->assertSuccessful()
        ->assertViewIs('newsletter.status')
        ->assertSee('Your email address has been confirmed. You will receive the weekly newsletter.');

    expect($subscriber->fresh()->status)->toBe(NewsletterSubscriberStatus::Confirmed)
        ->and($subscriber->fresh()->confirmed_at)->not->toBeNull();
});

test('newsletter status pages return German subscribers to the German start page', function () {
    $subscriber = NewsletterSubscriber::factory()->create([
        'locale' => Language::German,
    ]);

    $this->post(newsletterConfirmationUrl($subscriber))
        ->assertSuccessful()
        ->assertSee('href="'.route('magazine.localized.index', ['locale' => 'de']).'"', false);
});

test('unsigned and expired newsletter confirmation links are rejected', function () {
    $subscriber = NewsletterSubscriber::factory()->create();
    $expiredConfirmationUrl = URL::temporarySignedRoute(
        'newsletter.confirm',
        now()->subMinute(),
        [
            'newsletterSubscriber' => $subscriber,
            'token' => $subscriber->action_token,
        ],
    );

    $this->get(route('newsletter.confirm', [
        'newsletterSubscriber' => $subscriber,
        'token' => $subscriber->action_token,
    ]))
        ->assertForbidden();

    $this->get($expiredConfirmationUrl)->assertForbidden();

    expect($subscriber->fresh()->status)->toBe(NewsletterSubscriberStatus::Pending);
});

test('confirmation is idempotent and an unsubscribe cannot be reversed by an old confirmation link', function () {
    $subscriber = NewsletterSubscriber::factory()->create();
    $confirmationUrl = newsletterConfirmationUrl($subscriber);

    $this->post($confirmationUrl)->assertSuccessful();
    $this->post($confirmationUrl)
        ->assertSuccessful()
        ->assertSee('This email address has already been confirmed.');

    $unsubscribeUrl = URL::signedRoute(
        'newsletter.unsubscribe',
        [
            'newsletterSubscriber' => $subscriber,
            'token' => $subscriber->action_token,
        ],
    );

    $this->get($unsubscribeUrl)
        ->assertSuccessful()
        ->assertViewIs('newsletter.action')
        ->assertSee('Unsubscribe from newsletter');

    $this->post($unsubscribeUrl)
        ->assertSuccessful()
        ->assertSee('You have been unsubscribed from the newsletter.');

    $this->get($confirmationUrl)
        ->assertSuccessful()
        ->assertSee('cannot be confirmed because the address has already been unsubscribed.');

    expect($subscriber->fresh()->status)->toBe(NewsletterSubscriberStatus::Unsubscribed)
        ->and($subscriber->fresh()->unsubscribed_at)->not->toBeNull();
});

test('a new newsletter attempt rotates the action token and invalidates old links', function () {
    Mail::fake();

    $subscriber = NewsletterSubscriber::factory()->unsubscribed()->create([
        'email' => 'reader@example.com',
    ]);
    $oldConfirmationUrl = newsletterConfirmationUrl($subscriber);

    $this->post(route('newsletter.store'), [
        'email' => $subscriber->email,
        'locale' => Language::German->value,
        'consent' => 'on',
    ])->assertRedirect();

    $subscriber->refresh();

    expect($subscriber->status)->toBe(NewsletterSubscriberStatus::Pending)
        ->and($subscriber->action_token)->not->toBeNull();

    $this->get($oldConfirmationUrl)->assertNotFound();
});

function newsletterConfirmationUrl(NewsletterSubscriber $subscriber): string
{
    return URL::temporarySignedRoute(
        'newsletter.confirm',
        now()->addHour(),
        [
            'newsletterSubscriber' => $subscriber,
            'token' => $subscriber->action_token,
        ],
    );
}
