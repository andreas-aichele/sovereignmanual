<?php

namespace App\Providers;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Pillar;
use App\Models\Post;
use App\Support\Locales;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AppServiceProvider extends ServiceProvider
{
    private const int MINIMUM_PILLAR_POSTS = 6;

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configurePublicNavigation();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    protected function configurePublicNavigation(): void
    {
        ViewFacade::composer('components.public-nav', function (View $view): void {
            $viewData = $view->getData();
            $locale = $viewData['locale'] ?? App::currentLocale();
            $locale = is_string($locale) && Locales::isSupported($locale)
                ? $locale
                : Locales::fallback();
            $hasPillarNavItems = array_key_exists('pillarNavItems', $viewData);
            $hasCategoryNavItems = array_key_exists('categoryNavItems', $viewData);
            $publishedPostCounts = $hasPillarNavItems && $hasCategoryNavItems
                ? []
                : $this->publishedPostCountsByCategoryKey($locale);

            $view->with([
                'pillarNavItems' => $hasPillarNavItems
                    ? $viewData['pillarNavItems']
                    : $this->pillarNavItems($locale, $publishedPostCounts),
                'categoryNavItems' => $hasCategoryNavItems
                    ? $viewData['categoryNavItems']
                    : $this->categoryNavItems($locale, $publishedPostCounts),
            ]);
        });
    }

    /**
     * @param  array<string, int>  $publishedPostCounts
     * @return array<int, array{label: string, url: string}>
     */
    private function pillarNavItems(string $locale, array $publishedPostCounts): array
    {
        $pillarOrder = array_flip(Pillar::NAVIGATION_ORDER);

        return $this->localizedPillars($locale)
            ->filter(fn (Pillar $pillar): bool => $this->publishedPostCountForPillar($pillar, $publishedPostCounts) >= self::MINIMUM_PILLAR_POSTS)
            ->sortBy(fn (Pillar $pillar): array => [
                $pillarOrder[$pillar->key] ?? PHP_INT_MAX,
                $pillar->name,
            ])
            ->map(fn (Pillar $pillar): array => [
                'label' => $pillar->label($locale),
                'url' => $this->localizedPillarRoute($locale, $pillar),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, int>  $publishedPostCounts
     * @return array<int, array{label: string, url: string}>
     */
    private function categoryNavItems(string $locale, array $publishedPostCounts): array
    {
        $categoryOrder = array_flip(Category::NAVIGATION_ORDER);

        return Category::query()
            ->where('lang', Locales::language($locale))
            ->get(['key', 'lang', 'slug', 'name'])
            ->filter(fn (Category $category): bool => ($publishedPostCounts[$category->key] ?? 0) > 0)
            ->sortBy(fn (Category $category): array => [
                $categoryOrder[$category->key] ?? PHP_INT_MAX,
                $category->name,
            ])
            ->map(fn (Category $category): array => [
                'label' => $category->label($locale),
                'url' => $locale === Locales::fallback()
                    ? route('magazine.category', ['category' => $category->localizedSlug($locale)])
                    : route('magazine.localized.category', [
                        'locale' => $locale,
                        'category' => $category->localizedSlug($locale),
                    ]),
            ])
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, Pillar>
     */
    private function localizedPillars(string $locale): Collection
    {
        return Pillar::query()
            ->with('categories:id,pillar_id,key')
            ->where('lang', Locales::language($locale))
            ->get(['id', 'key', 'lang', 'slug', 'name', 'description']);
    }

    /**
     * @param  array<string, int>  $publishedPostCounts
     */
    private function publishedPostCountForPillar(Pillar $pillar, array $publishedPostCounts): int
    {
        return $pillar->categories
            ->pluck('key')
            ->unique()
            ->sum(fn (string $categoryKey): int => $publishedPostCounts[$categoryKey] ?? 0);
    }

    /**
     * @return array<string, int>
     */
    private function publishedPostCountsByCategoryKey(string $locale): array
    {
        $postTable = (new Post)->getTable();
        $categoryTable = (new Category)->getTable();

        return Post::query()
            ->select("{$categoryTable}.key")
            ->selectRaw('count(*) as post_count')
            ->join($categoryTable, "{$categoryTable}.id", '=', "{$postTable}.category_id")
            ->where("{$postTable}.status", PostStatus::Published)
            ->whereNotNull("{$postTable}.published_at")
            ->where("{$postTable}.published_at", '<=', now())
            ->whereHas('translations', fn ($query) => $query->where('locale', $locale))
            ->groupBy("{$categoryTable}.key")
            ->pluck('post_count', 'key')
            ->map(fn (mixed $count): int => (int) $count)
            ->all();
    }

    private function localizedPillarRoute(string $locale, Pillar $pillar): string
    {
        if ($locale === Locales::fallback()) {
            return route('magazine.pillar.show', ['pillar' => $pillar->localizedSlug($locale)]);
        }

        return route('magazine.localized.pillar.show', [
            'locale' => $locale,
            'pillar' => $pillar->localizedSlug($locale),
        ]);
    }
}
