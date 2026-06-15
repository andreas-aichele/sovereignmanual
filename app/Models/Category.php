<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'slug',
    'name',
])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    public function label(string $locale): string
    {
        return $this->name[$locale]
            ?? $this->name['en']
            ?? Str::of($this->slug)->replace('-', ' ')->title()->toString();
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

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'name' => 'array',
        ];
    }
}
