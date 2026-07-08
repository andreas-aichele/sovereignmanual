<?php

namespace App\Models;

use App\Enums\Language;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'key',
    'lang',
    'slug',
    'name',
    'description',
])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    public const NAVIGATION_ORDER = [
        'news',
        'financial-sovereignty',
        'mindset',
        'self-custody',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lang' => Language::class,
        ];
    }

    public function label(?string $locale = null): string
    {
        return $this->localized($locale)->name;
    }

    public function localizedSlug(?string $locale = null): string
    {
        return $this->localized($locale)->slug;
    }

    public function localizedDescription(?string $locale = null): string
    {
        return $this->localized($locale)->description;
    }

    public function matchesSlug(string $slug, string $locale): bool
    {
        return $this->localizedSlug($locale) === $slug
            || $this->key === $slug;
    }

    public function localized(?string $locale = null): self
    {
        $language = $locale === null
            ? $this->lang
            : (Language::fromLocale($locale) ?? $this->lang);

        if ($this->lang === $language) {
            return $this;
        }

        return self::query()
            ->where('key', $this->key)
            ->where('lang', $language)
            ->first()
            ?? $this;
    }

    /**
     * @return HasMany<Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * @return HasMany<ContentTopic, $this>
     */
    public function contentTopics(): HasMany
    {
        return $this->hasMany(ContentTopic::class);
    }
}
