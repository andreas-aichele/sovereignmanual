<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\Encoders\AvifEncoder;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Throwable;

class ResponsiveImage
{
    /**
     * @param  array<int, int>  $widths
     * @return array{src: string, width: int, height: int, sources: array<string, array<int, array{url: string, width: int, height: int}>>}|null
     */
    public function generate(string $disk, string $path, array $widths = [480, 768, 1024, 1360, 1800]): ?array
    {
        $storage = Storage::disk($disk);
        $src = $storage->url($path);
        $absolutePath = method_exists($storage, 'path') ? $storage->path($path) : null;

        if (! is_string($absolutePath) || ! is_file($absolutePath)) {
            return null;
        }

        try {
            $manager = $this->manager();
            $image = $manager->decode($absolutePath);
        } catch (Throwable) {
            return null;
        }

        $originalWidth = $image->width();
        $originalHeight = $image->height();
        $baseDirectory = trim(dirname($path), '.');
        $targetDirectory = ($baseDirectory === '' ? '' : "{$baseDirectory}/").'responsive';
        $name = Str::of(basename($path))
            ->beforeLast('.')
            ->slug()
            ->toString() ?: 'image';
        $sources = [];

        foreach ($this->targetWidths($widths, $originalWidth) as $width) {
            $resized = $manager->decode($absolutePath)->scaleDown(width: $width);
            $height = $resized->height();

            foreach ($this->encoders() as $format => $encoder) {
                try {
                    $variantPath = "{$targetDirectory}/{$name}-{$width}.{$format}";
                    $storage->put($variantPath, (string) $resized->encode($encoder), 'public');
                    $sources[$format][] = [
                        'url' => $storage->url($variantPath),
                        'width' => $width,
                        'height' => $height,
                    ];
                } catch (Throwable) {
                    continue;
                }
            }
        }

        return $sources === [] ? null : [
            'src' => $src,
            'width' => $originalWidth,
            'height' => $originalHeight,
            'sources' => $sources,
        ];
    }

    public function convertToJpeg(string $disk, string $sourcePath, string $targetPath): ?string
    {
        $storage = Storage::disk($disk);
        $absolutePath = method_exists($storage, 'path') ? $storage->path($sourcePath) : null;

        if (! is_string($absolutePath) || ! is_file($absolutePath)) {
            return null;
        }

        try {
            $image = $this->manager()->decode($absolutePath);
            $storage->put($targetPath, (string) $image->encode(new JpegEncoder(quality: 88, progressive: true, strip: true)), 'public');
        } catch (Throwable) {
            return null;
        }

        return $targetPath;
    }

    private function manager(): ImageManager
    {
        if (extension_loaded('imagick')) {
            return new ImageManager(ImagickDriver::class);
        }

        return new ImageManager(GdDriver::class);
    }

    /**
     * @param  array<int, int>  $widths
     * @return array<int, int>
     */
    private function targetWidths(array $widths, int $originalWidth): array
    {
        $targets = collect($widths)
            ->push($originalWidth)
            ->map(fn (int $width): int => min($width, $originalWidth))
            ->unique()
            ->sort()
            ->values()
            ->all();

        return array_values(array_filter($targets, fn (int $width): bool => $width > 0));
    }

    /**
     * @return array<string, object>
     */
    private function encoders(): array
    {
        return [
            'avif' => new AvifEncoder(quality: 52),
            'webp' => new WebpEncoder(quality: 78),
            'jpg' => new JpegEncoder(quality: 82, progressive: true, strip: true),
        ];
    }
}
