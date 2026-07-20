<?php

use App\Enums\Language;
use App\Enums\WaitlistSubscriberStatus;
use App\Mail\ConfirmWaitlistSubscription;
use App\Models\WaitlistSubscriber;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

test('submitting the waitlist form creates a pending subscription and queues confirmation', function () {
    Mail::fake();

    $this->from(route('magazine.index'))
        ->post(route('waitlist.store'), [
            'email' => ' Reader@Example.com ',
            'locale' => Language::German->value,
            'consent' => 'on',
        ])
        ->assertRedirect(route('magazine.index'))
        ->assertSessionHas('waitlist_status');

    $subscriber = WaitlistSubscriber::query()->sole();

    expect($subscriber->email)->toBe('reader@example.com')
        ->and($subscriber->locale)->toBe(Language::German)
        ->and($subscriber->status)->toBe(WaitlistSubscriberStatus::Pending)
        ->and($subscriber->action_token)->not->toBeNull()
        ->and($subscriber->consented_at)->not->toBeNull()
        ->and($subscriber->confirmed_at)->toBeNull()
        ->and($subscriber->unsubscribed_at)->toBeNull();

    Mail::assertQueued(ConfirmWaitlistSubscription::class, function (ConfirmWaitlistSubscription $mail) use ($subscriber): bool {
        return $mail->waitlistSubscriber->is($subscriber)
            && $mail->hasTo($subscriber->email);
    });
});

test('waitlist submissions require consent and a supported locale', function () {
    Mail::fake();

    $this->from(route('magazine.index'))
        ->post(route('waitlist.store'), [
            'email' => 'reader@example.com',
            'locale' => 'fr',
        ])
        ->assertRedirect(route('magazine.index'))
        ->assertSessionHasErrors(['locale', 'consent']);

    expect(WaitlistSubscriber::query()->count())->toBe(0);

    Mail::assertNothingQueued();
});

test('a confirmed subscription is not re-confirmed or emailed again', function () {
    Mail::fake();

    $subscriber = WaitlistSubscriber::factory()->confirmed()->create([
        'email' => 'reader@example.com',
        'locale' => Language::German,
    ]);

    $consentedAt = $subscriber->consented_at;

    $this->from(route('magazine.index'))
        ->post(route('waitlist.store'), [
            'email' => 'reader@example.com',
            'locale' => Language::English->value,
            'consent' => 'on',
        ])
        ->assertRedirect(route('magazine.index'));

    expect($subscriber->fresh()->status)->toBe(WaitlistSubscriberStatus::Confirmed)
        ->and($subscriber->fresh()->locale)->toBe(Language::German)
        ->and($subscriber->fresh()->consented_at->equalTo($consentedAt))->toBeTrue();

    Mail::assertNothingQueued();
});

test('an unsubscribed address can start a new confirmation flow', function () {
    Mail::fake();

    $subscriber = WaitlistSubscriber::factory()->unsubscribed()->create([
        'email' => 'reader@example.com',
    ]);

    $this->from(route('magazine.index'))
        ->post(route('waitlist.store'), [
            'email' => 'reader@example.com',
            'locale' => Language::German->value,
            'consent' => 'on',
        ])
        ->assertRedirect(route('magazine.index'));

    expect($subscriber->fresh()->status)->toBe(WaitlistSubscriberStatus::Pending)
        ->and($subscriber->fresh()->locale)->toBe(Language::German)
        ->and($subscriber->fresh()->confirmed_at)->toBeNull()
        ->and($subscriber->fresh()->unsubscribed_at)->toBeNull();

    Mail::assertQueued(ConfirmWaitlistSubscription::class);
});

test('the confirmation email is localized and contains signed confirmation and unsubscribe links', function () {
    $subscriber = WaitlistSubscriber::factory()->create([
        'locale' => Language::German,
    ]);

    (new ConfirmWaitlistSubscription($subscriber))
        ->assertSeeInHtml('Anmeldung prüfen und bestätigen')
        ->assertSeeInHtml('waitlist/confirm')
        ->assertSeeInHtml('waitlist/unsubscribe')
        ->assertSeeInHtml('signature=');
});

test('a temporary signed link confirms a pending subscription', function () {
    $subscriber = WaitlistSubscriber::factory()->create();

    $confirmationUrl = URL::temporarySignedRoute(
        'waitlist.confirm',
        now()->addHour(),
        [
            'waitlistSubscriber' => $subscriber,
            'token' => $subscriber->action_token,
        ],
    );

    $this->get($confirmationUrl)
        ->assertSuccessful()
        ->assertViewIs('waitlist.action')
        ->assertSee('Confirm your waitlist subscription');

    expect($subscriber->fresh()->status)->toBe(WaitlistSubscriberStatus::Pending);

    $this->post($confirmationUrl)
        ->assertSuccessful()
        ->assertViewIs('waitlist.status')
        ->assertSee('Your email address has been confirmed.');

    expect($subscriber->fresh()->status)->toBe(WaitlistSubscriberStatus::Confirmed)
        ->and($subscriber->fresh()->confirmed_at)->not->toBeNull();
});

test('waitlist status pages return German subscribers to the German start page', function () {
    $subscriber = WaitlistSubscriber::factory()->create([
        'locale' => Language::German,
    ]);
    $confirmationUrl = URL::temporarySignedRoute(
        'waitlist.confirm',
        now()->addHour(),
        [
            'waitlistSubscriber' => $subscriber,
            'token' => $subscriber->action_token,
        ],
    );

    $this->post($confirmationUrl)
        ->assertSuccessful()
        ->assertSee('href="'.route('magazine.localized.index', ['locale' => 'de']).'"', false);
});

test('unsigned and expired confirmation links are rejected', function () {
    $subscriber = WaitlistSubscriber::factory()->create();

    $expiredConfirmationUrl = URL::temporarySignedRoute(
        'waitlist.confirm',
        now()->subMinute(),
        [
            'waitlistSubscriber' => $subscriber,
            'token' => $subscriber->action_token,
        ],
    );

    $this->get(route('waitlist.confirm', [
        'waitlistSubscriber' => $subscriber,
        'token' => $subscriber->action_token,
    ]))
        ->assertForbidden();

    $this->get($expiredConfirmationUrl)
        ->assertForbidden();

    expect($subscriber->fresh()->status)->toBe(WaitlistSubscriberStatus::Pending);
});

test('confirmation is idempotent and an unsubscribe cannot be reversed by an old confirmation link', function () {
    $subscriber = WaitlistSubscriber::factory()->create();

    $confirmationUrl = URL::temporarySignedRoute(
        'waitlist.confirm',
        now()->addHour(),
        [
            'waitlistSubscriber' => $subscriber,
            'token' => $subscriber->action_token,
        ],
    );

    $this->get($confirmationUrl)
        ->assertSuccessful()
        ->assertSee('Confirm your waitlist subscription');
    $this->post($confirmationUrl)->assertSuccessful();
    $this->post($confirmationUrl)
        ->assertSuccessful()
        ->assertSee('This email address has already been confirmed.');

    expect($subscriber->fresh()->status)->toBe(WaitlistSubscriberStatus::Confirmed);

    $unsubscribeUrl = URL::signedRoute(
        'waitlist.unsubscribe',
        [
            'waitlistSubscriber' => $subscriber,
            'token' => $subscriber->action_token,
        ],
    );

    $this->get($unsubscribeUrl)
        ->assertSuccessful()
        ->assertViewIs('waitlist.action')
        ->assertSee('Unsubscribe from the waitlist');

    expect($subscriber->fresh()->status)->toBe(WaitlistSubscriberStatus::Confirmed);

    $this->post($unsubscribeUrl)
        ->assertSuccessful()
        ->assertSee('You have been unsubscribed from the waitlist.');

    $this->get($confirmationUrl)
        ->assertSuccessful()
        ->assertSee('cannot be confirmed because the address has already been unsubscribed.');

    expect($subscriber->fresh()->status)->toBe(WaitlistSubscriberStatus::Unsubscribed)
        ->and($subscriber->fresh()->unsubscribed_at)->not->toBeNull();

    $this->get($unsubscribeUrl)
        ->assertSuccessful()
        ->assertSee('This email address is already unsubscribed from the waitlist.');
});

test('a new waitlist attempt rotates the action token and invalidates old links', function () {
    Mail::fake();

    $subscriber = WaitlistSubscriber::factory()->unsubscribed()->create([
        'email' => 'reader@example.com',
    ]);
    $oldConfirmationUrl = URL::temporarySignedRoute(
        'waitlist.confirm',
        now()->addHour(),
        [
            'waitlistSubscriber' => $subscriber,
            'token' => $subscriber->action_token,
        ],
    );

    $this->post(route('waitlist.store'), [
        'email' => $subscriber->email,
        'locale' => Language::German->value,
        'consent' => 'on',
    ])->assertRedirect();

    $subscriber->refresh();

    expect($subscriber->status)->toBe(WaitlistSubscriberStatus::Pending)
        ->and($subscriber->action_token)->not->toBeNull();

    $this->get($oldConfirmationUrl)->assertNotFound();
});
