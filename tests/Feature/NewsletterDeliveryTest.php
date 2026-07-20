<?php

use App\Enums\Language;
use App\Enums\NewsletterDeliveryStatus;
use App\Enums\PostStatus;
use App\Jobs\SendNewsletterDelivery;
use App\Mail\NewsletterIssueMail;
use App\Models\Category;
use App\Models\NewsletterDelivery;
use App\Models\NewsletterIssue;
use App\Models\NewsletterSubscriber;
use App\Models\Post;
use App\Models\PostTranslation;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

test('the weekly newsletter automatically queues localized summaries for confirmed subscribers', function () {
    Queue::fake();
    $this->travelTo('2026-07-17 08:00:00');

    try {
        $categories = newsletterCategories();
        $post = weeklyNewsletterPost($categories['en'], now()->subDays(2));
        PostTranslation::factory()->create([
            'post_id' => $post->id,
            'locale' => Language::German->value,
            'title' => 'Deutscher Wochenartikel',
            'slug' => 'deutscher-wochenartikel',
            'excerpt' => 'Eine deutschsprachige Zusammenfassung.',
        ]);

        NewsletterSubscriber::factory()->confirmed()->create(['locale' => Language::English]);
        NewsletterSubscriber::factory()->confirmed()->create(['locale' => Language::German]);
        NewsletterSubscriber::factory()->create(['locale' => Language::English]);
        NewsletterSubscriber::factory()->unsubscribed()->create(['locale' => Language::German]);

        $this->artisan('app:send-weekly-newsletter')->assertSuccessful();

        expect(NewsletterIssue::query()->count())->toBe(2)
            ->and(NewsletterDelivery::query()->count())->toBe(2);

        $germanIssue = NewsletterIssue::query()
            ->where('locale', Language::German)
            ->sole();

        expect($germanIssue->subject)->toBe('Sovereign Manual Wochenrückblick – 10.07.2026 bis 17.07.2026')
            ->and($germanIssue->posts)->toHaveCount(1)
            ->and($germanIssue->posts[0]['title'])->toBe('Deutscher Wochenartikel')
            ->and($germanIssue->posts[0]['url'])->toBe(route('magazine.localized.show', [
                'locale' => 'de',
                'category' => $categories['de']->slug,
                'slug' => 'deutscher-wochenartikel',
            ]));

        Queue::assertPushed(SendNewsletterDelivery::class, 2);
    } finally {
        $this->travelBack();
    }
});

test('the weekly newsletter includes every post published during the preceding seven days', function () {
    Queue::fake();
    $this->travelTo('2026-07-17 08:00:00');

    try {
        $category = newsletterCategories()['en'];
        $periodStart = now('Europe/Berlin')
            ->startOfDay()
            ->setTime(8, 0)
            ->subWeek()
            ->setTimezone(config('app.timezone'));

        weeklyNewsletterPost($category, now()->subDays(2), 'included-weekly-post');
        weeklyNewsletterPost($category, $periodStart, 'weekly-boundary-post');
        weeklyNewsletterPost($category, $periodStart->copy()->subSecond(), 'older-weekly-post');
        weeklyNewsletterPost($category, now()->subHours(3), 'friday-morning-post');
        NewsletterSubscriber::factory()->confirmed()->create(['locale' => Language::English]);

        $this->artisan('app:send-weekly-newsletter')->assertSuccessful();

        $issue = NewsletterIssue::query()->where('locale', Language::English)->sole();

        $urls = implode(' ', collect($issue->posts)->pluck('url')->all());

        expect($issue->posts)->toHaveCount(3)
            ->and($urls)->toContain('included-weekly-post')
            ->toContain('weekly-boundary-post')
            ->toContain('friday-morning-post')
            ->not->toContain('older-weekly-post');
    } finally {
        $this->travelBack();
    }
});

test('running the weekly newsletter later on the same Friday does not create duplicate deliveries', function () {
    Queue::fake();
    $this->travelTo('2026-07-17 08:00:00');

    try {
        $category = newsletterCategories()['en'];
        weeklyNewsletterPost($category, now()->subDay());
        NewsletterSubscriber::factory()->confirmed()->create(['locale' => Language::English]);

        $this->artisan('app:send-weekly-newsletter')->assertSuccessful();
        $this->travelTo('2026-07-17 08:05:00');
        $this->artisan('app:send-weekly-newsletter')->assertSuccessful();

        expect(NewsletterIssue::query()->count())->toBe(1)
            ->and(NewsletterDelivery::query()->count())->toBe(1);
    } finally {
        $this->travelBack();
    }
});

test('an existing weekly issue requeues a pending delivery after a queue interruption', function () {
    Queue::fake();
    $this->travelTo('2026-07-17 08:00:00');

    try {
        $category = newsletterCategories()['en'];
        weeklyNewsletterPost($category, now()->subDay());
        $periodEndInBerlin = now('Europe/Berlin')->startOfDay()->setTime(8, 0);
        $periodStartInBerlin = $periodEndInBerlin->copy()->subWeek();
        $periodEnd = $periodEndInBerlin->setTimezone(config('app.timezone'));
        $periodStart = $periodStartInBerlin->setTimezone(config('app.timezone'));
        $issue = NewsletterIssue::factory()->create([
            'fingerprint' => hash('sha256', json_encode([
                'locale' => Language::English->value,
                'period_start' => $periodStart->toIso8601String(),
                'period_end' => $periodEnd->toIso8601String(),
            ], JSON_THROW_ON_ERROR)),
            'locale' => Language::English,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
        ]);
        $subscriber = NewsletterSubscriber::factory()->confirmed()->create(['locale' => Language::English]);
        NewsletterDelivery::factory()->create([
            'newsletter_issue_id' => $issue->id,
            'newsletter_subscriber_id' => $subscriber->id,
        ]);

        $this->artisan('app:send-weekly-newsletter')->assertSuccessful();

        expect(NewsletterDelivery::query()->count())->toBe(1);

        Queue::assertPushed(SendNewsletterDelivery::class, 1);
    } finally {
        $this->travelBack();
    }
});

test('the weekly period remains anchored to Friday morning in Berlin across daylight saving time', function () {
    Queue::fake();
    $this->travelTo(Carbon\Carbon::parse('2026-04-03 08:05:00', 'Europe/Berlin'));

    try {
        $category = newsletterCategories()['en'];
        weeklyNewsletterPost($category, now()->subDay());
        NewsletterSubscriber::factory()->confirmed()->create(['locale' => Language::English]);

        $this->artisan('app:send-weekly-newsletter')->assertSuccessful();

        $issue = NewsletterIssue::query()->where('locale', Language::English)->sole();

        expect($issue->period_start->setTimezone('Europe/Berlin')->format('Y-m-d H:i'))->toBe('2026-03-27 08:00')
            ->and($issue->period_end->setTimezone('Europe/Berlin')->format('Y-m-d H:i'))->toBe('2026-04-03 08:00');
    } finally {
        $this->travelBack();
    }
});

test('the weekly newsletter does not create an issue without new published posts', function () {
    Queue::fake();
    NewsletterSubscriber::factory()->confirmed()->create(['locale' => Language::English]);

    $this->artisan('app:send-weekly-newsletter')
        ->expectsOutput('No published posts were available for the weekly newsletter.')
        ->assertSuccessful();

    expect(NewsletterIssue::query()->count())->toBe(0)
        ->and(NewsletterDelivery::query()->count())->toBe(0);

    Queue::assertNothingPushed();
});

test('a delivery rechecks an unsubscribe before sending the newsletter', function () {
    Mail::fake();

    $issue = NewsletterIssue::factory()->create(['locale' => Language::English]);
    $subscriber = NewsletterSubscriber::factory()->unsubscribed()->create(['locale' => Language::English]);
    $delivery = NewsletterDelivery::factory()->create([
        'newsletter_issue_id' => $issue->id,
        'newsletter_subscriber_id' => $subscriber->id,
    ]);

    (new SendNewsletterDelivery($delivery->id))->handle();

    expect($delivery->fresh()->status)->toBe(NewsletterDeliveryStatus::Skipped);

    Mail::assertNothingSent();
});

test('a confirmed matching subscriber receives a weekly newsletter with an unsubscribe link', function () {
    Mail::fake();

    $subscriber = NewsletterSubscriber::factory()->confirmed()->create(['locale' => Language::German]);
    $issue = NewsletterIssue::factory()->create([
        'locale' => Language::German,
        'subject' => 'Wöchentliche Zusammenfassung',
        'intro' => 'Neue praktische Beiträge.',
        'posts' => [[
            'title' => 'Sicher kommunizieren',
            'excerpt' => 'Ein kurzer Einstieg.',
            'url' => 'https://example.com/de/privatsphaere-sicherheit/sicher-kommunizieren',
        ]],
    ]);
    $delivery = NewsletterDelivery::factory()->create([
        'newsletter_issue_id' => $issue->id,
        'newsletter_subscriber_id' => $subscriber->id,
    ]);

    (new SendNewsletterDelivery($delivery->id))->handle();

    expect($delivery->fresh()->status)->toBe(NewsletterDeliveryStatus::Sent)
        ->and($delivery->fresh()->sent_at)->not->toBeNull();

    Mail::assertSent(NewsletterIssueMail::class, function (NewsletterIssueMail $mail) use ($subscriber): bool {
        return $mail->hasTo($subscriber->email);
    });

    (new NewsletterIssueMail($delivery->fresh(['issue', 'subscriber'])))
        ->assertSeeInHtml('Sicher kommunizieren')
        ->assertSeeInHtml('Newsletter abmelden')
        ->assertSeeInHtml('newsletter/unsubscribe');
});

test('the weekly newsletter command is scheduled every Friday', function () {
    $schedule = file_get_contents(base_path('routes/console.php'));

    expect($schedule)
        ->toContain("Schedule::command('app:send-weekly-newsletter')")
        ->toContain('->fridays()')
        ->toContain("->at('08:00')")
        ->toContain("->timezone('Europe/Berlin')")
        ->toContain('->onOneServer()');
});

/**
 * @return array{en: Category, de: Category}
 */
function newsletterCategories(): array
{
    $english = Category::factory()->create([
        'key' => 'privacy-security',
        'lang' => Language::English,
        'slug' => 'privacy-security',
        'name' => 'Privacy & Security',
    ]);
    $german = Category::factory()->create([
        'key' => 'privacy-security',
        'lang' => Language::German,
        'slug' => 'privatsphaere-sicherheit',
        'name' => 'Privatsphäre & Sicherheit',
    ]);

    return ['en' => $english, 'de' => $german];
}

function weeklyNewsletterPost(Category $category, DateTimeInterface $publishedAt, string $slug = 'weekly-post'): Post
{
    $post = Post::factory()->create([
        'category_id' => $category->id,
        'status' => PostStatus::Published,
        'published_at' => $publishedAt,
        'scheduled_for' => null,
    ]);
    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => Language::English->value,
        'title' => str($slug)->replace('-', ' ')->title()->toString(),
        'slug' => $slug,
        'excerpt' => 'A practical weekly summary.',
    ]);

    return $post;
}
