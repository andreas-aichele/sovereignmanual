<?php

namespace App\Actions;

use App\Enums\Language;
use App\Enums\NewsletterDeliveryStatus;
use App\Enums\NewsletterSubscriberStatus;
use App\Enums\PostStatus;
use App\Jobs\SendNewsletterDelivery;
use App\Models\NewsletterDelivery;
use App\Models\NewsletterIssue;
use App\Models\NewsletterSubscriber;
use App\Models\Post;
use App\Models\PostTranslation;
use App\Support\Locales;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DispatchWeeklyNewsletter
{
    /**
     * @return array<int, NewsletterIssue>
     */
    public function handle(CarbonInterface $runAt): array
    {
        $periodEndInBerlin = $this->weeklyPeriodEnd($runAt);
        $periodStartInBerlin = $periodEndInBerlin->copy()->subWeek();
        $periodEnd = $periodEndInBerlin->setTimezone(config('app.timezone'));
        $periodStart = $periodStartInBerlin->setTimezone(config('app.timezone'));

        return collect(Language::cases())
            ->map(fn (Language $locale): ?NewsletterIssue => $this->dispatchForLocale($locale, $periodStart, $periodEnd))
            ->filter()
            ->values()
            ->all();
    }

    private function dispatchForLocale(
        Language $locale,
        CarbonInterface $periodStart,
        CarbonInterface $periodEnd,
    ): ?NewsletterIssue {
        $posts = Post::query()
            ->with(['category', 'translations'])
            ->where('status', PostStatus::Published)
            ->where('published_at', '>=', $periodStart)
            ->where('published_at', '<', $periodEnd)
            ->whereHas('category')
            ->whereHas('translations', fn ($query) => $query->where('locale', $locale->value))
            ->latest('published_at')
            ->get();
        $newsletterPosts = $posts
            ->map(fn (Post $post): array => $this->serializePost($post, $locale))
            ->all();

        if ($newsletterPosts === []) {
            return null;
        }

        $fingerprint = hash('sha256', json_encode([
            'locale' => $locale->value,
            'period_start' => $periodStart->toIso8601String(),
            'period_end' => $periodEnd->toIso8601String(),
        ], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($fingerprint, $locale, $periodStart, $periodEnd, $newsletterPosts): NewsletterIssue {
            $issue = NewsletterIssue::query()->firstOrCreate(
                ['fingerprint' => $fingerprint],
                [
                    'locale' => $locale,
                    'subject' => $this->subject($locale, $periodStart, $periodEnd),
                    'intro' => __('newsletter.weekly.intro', [], $locale->value),
                    'posts' => $newsletterPosts,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'queued_at' => now(),
                ],
            );

            if ($issue->wasRecentlyCreated) {
                NewsletterSubscriber::query()
                    ->where('status', NewsletterSubscriberStatus::Confirmed)
                    ->where('locale', $locale)
                    ->orderBy('id')
                    ->eachById(function (NewsletterSubscriber $subscriber) use ($issue): void {
                        NewsletterDelivery::query()->create([
                            'newsletter_issue_id' => $issue->id,
                            'newsletter_subscriber_id' => $subscriber->id,
                            'status' => NewsletterDeliveryStatus::Pending,
                            'queued_at' => now(),
                        ]);
                    });
            }

            NewsletterDelivery::query()
                ->where('newsletter_issue_id', $issue->id)
                ->where('status', NewsletterDeliveryStatus::Pending)
                ->orderBy('id')
                ->eachById(function (NewsletterDelivery $delivery): void {
                    SendNewsletterDelivery::dispatch($delivery->id)
                        ->afterCommit();
                });

            return $issue;
        });
    }

    /**
     * @return array{title: string, excerpt: string, url: string}
     */
    private function serializePost(Post $post, Language $locale): array
    {
        if ($post->category === null) {
            throw new InvalidArgumentException('Every newsletter post must have a category.');
        }

        $translation = $post->translations->firstWhere('locale', $locale->value);

        if (! $translation instanceof PostTranslation) {
            throw new InvalidArgumentException('Every newsletter post must be translated for the selected locale.');
        }

        return [
            'title' => $translation->title,
            'excerpt' => $translation->excerpt,
            'url' => $this->postUrl($locale, $post->category->localizedSlug($locale->value), $translation->slug),
        ];
    }

    private function subject(Language $locale, CarbonInterface $periodStart, CarbonInterface $periodEnd): string
    {
        return __('newsletter.weekly.subject', [
            'start' => $this->formatDate($periodStart, $locale),
            'end' => $this->formatDate($periodEnd, $locale),
        ], $locale->value);
    }

    private function formatDate(CarbonInterface $date, Language $locale): string
    {
        $date = $date->copy()->setTimezone('Europe/Berlin');

        return $locale === Language::German
            ? $date->format('d.m.Y')
            : $date->format('M j, Y');
    }

    private function weeklyPeriodEnd(CarbonInterface $runAt): CarbonInterface
    {
        $runAt = $runAt->copy()->setTimezone('Europe/Berlin');
        $periodEnd = $runAt->copy()->startOfDay()->setTime(8, 0);
        $daysSinceFriday = ($runAt->dayOfWeek - CarbonInterface::FRIDAY + 7) % 7;

        if ($daysSinceFriday === 0 && $runAt->lessThan($periodEnd)) {
            $daysSinceFriday = 7;
        }

        return $periodEnd->subDays($daysSinceFriday);
    }

    private function postUrl(Language $locale, string $category, string $slug): string
    {
        if ($locale === Locales::fallbackLanguage()) {
            return route('magazine.show', compact('category', 'slug'));
        }

        return route('magazine.localized.show', [
            'locale' => $locale->value,
            'category' => $category,
            'slug' => $slug,
        ]);
    }
}
