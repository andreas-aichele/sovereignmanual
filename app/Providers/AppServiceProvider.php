<?php

namespace App\Providers;

use App\Models\Category;
use App\Support\Locales;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AppServiceProvider extends ServiceProvider
{
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
            $locale = $view->getData()['locale'] ?? App::currentLocale();
            $locale = is_string($locale) && Locales::isSupported($locale)
                ? $locale
                : Locales::fallback();
            $categoryOrder = array_flip(Category::NAVIGATION_ORDER);

            $categoryNavItems = Category::query()
                ->where('lang', Locales::language($locale))
                ->get(['key', 'lang', 'slug', 'name'])
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

            $view->with('categoryNavItems', $categoryNavItems);
        });
    }
}
