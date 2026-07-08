<?php

namespace App\Http\Controllers;

use App\Support\Locales;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Lang;

class AboutController extends Controller
{
    public function __invoke(): View
    {
        $locale = App::currentLocale();
        $copy = $this->translationArray('about', $locale);
        $meta = $this->translationArray('about_meta', $locale);
        $canonical = $this->canonical($locale);

        return view('magazine.about', [
            'locale' => $locale,
            'languageOptions' => $this->languageOptions($locale),
            'copy' => $copy,
            'meta' => [
                ...$meta,
                'canonical' => $canonical,
                'alternates' => $this->alternates(),
                'xDefault' => route('magazine.about'),
                'ogType' => 'website',
                'ogLocale' => Locales::language($locale)->openGraphLocale(),
                'ogLocaleAlternates' => collect(Locales::supported())
                    ->reject(fn (string $alternateLocale): bool => $alternateLocale === $locale)
                    ->map(fn (string $alternateLocale): string => Locales::language($alternateLocale)->openGraphLocale())
                    ->values()
                    ->all(),
                'structuredData' => [
                    '@context' => 'https://schema.org',
                    '@type' => 'AboutPage',
                    'name' => (string) $copy['heading'],
                    'url' => $canonical,
                    'inLanguage' => $locale,
                    'description' => (string) $meta['description'],
                    'isPartOf' => [
                        '@type' => 'WebSite',
                        'name' => 'Sovereign Manual',
                        'url' => route('magazine.index'),
                    ],
                ],
            ],
        ]);
    }

    private function canonical(string $locale): string
    {
        if (request()->route('locale') === $locale) {
            return $this->localizedRoute($locale);
        }

        return $locale === Locales::fallback()
            ? route('magazine.about')
            : $this->localizedRoute($locale);
    }

    /**
     * @return array<string, string>
     */
    private function alternates(): array
    {
        return collect(Locales::supported())
            ->mapWithKeys(fn (string $locale): array => [
                $locale => $this->localizedRoute($locale),
            ])
            ->all();
    }

    /**
     * @return array<int, array{locale: string, label: string, url: string, current: bool}>
     */
    private function languageOptions(string $currentLocale): array
    {
        return collect(Locales::supported())
            ->map(fn (string $locale): array => [
                'locale' => $locale,
                'label' => Locales::language($locale)->nativeName(),
                'url' => $this->localizedRoute($locale),
                'current' => $locale === $currentLocale,
            ])
            ->values()
            ->all();
    }

    private function localizedRoute(string $locale): string
    {
        return route('magazine.localized.about', ['locale' => $locale]);
    }

    /**
     * @return array<string, mixed>
     */
    private function translationArray(string $key, string $locale): array
    {
        /** @var array<string, mixed> $translation */
        $translation = Lang::get("magazine.{$key}", [], $locale);

        return $translation;
    }
}
