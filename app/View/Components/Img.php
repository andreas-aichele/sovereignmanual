<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Img extends Component
{
    /**
     * @param  array<string, mixed>|null  $responsive
     */
    public function __construct(
        public string $src,
        public string $alt = '',
        public ?array $responsive = null,
        public string $sizes = '100vw',
        public ?int $width = null,
        public ?int $height = null,
        public bool $hero = false,
    ) {
        if ($this->hasNoSources($this->responsive)) {
            $this->responsive = null;
        }

        $this->width ??= $this->integerValue($this->responsive['width'] ?? null);
        $this->height ??= $this->integerValue($this->responsive['height'] ?? null);
        $this->src = (string) ($this->responsive['src'] ?? $this->src);
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.img');
    }

    /**
     * @return array<string, string>
     */
    public function sourceSets(): array
    {
        return collect($this->responsive['sources'] ?? [])
            ->mapWithKeys(function (array $variants, string $format): array {
                $srcset = collect($variants)
                    ->sortBy('width')
                    ->map(fn (array $variant): string => "{$variant['url']} {$variant['width']}w")
                    ->implode(', ');

                return $srcset === '' ? [] : [$format => $srcset];
            })
            ->all();
    }

    public function loading(): string
    {
        return $this->hero ? 'eager' : 'lazy';
    }

    private function integerValue(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * @param  array<string, mixed>|null  $responsive
     */
    private function hasNoSources(?array $responsive): bool
    {
        return is_array($responsive)
            && collect($responsive['sources'] ?? [])->flatten(1)->isEmpty();
    }
}
