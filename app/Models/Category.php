<?php

namespace App\Models;

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
        $locale ??= $this->lang;

        if ($this->lang === $locale) {
            return $this;
        }

        return self::query()
            ->where('key', $this->key)
            ->where('lang', $locale)
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
