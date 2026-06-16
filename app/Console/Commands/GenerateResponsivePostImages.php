<?php

namespace App\Console\Commands;

use App\Models\PostAsset;
use App\Support\ResponsiveImage;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:generate-responsive-post-images {--force : Regenerate images that already have responsive metadata}')]
#[Description('Generate responsive variants for stored post images')]
class GenerateResponsivePostImages extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ResponsiveImage $responsiveImage): int
    {
        $processed = 0;
        $skipped = 0;
        $failed = 0;
        $failedAssetIds = [];

        $query = PostAsset::query()
            ->where('type', 'image')
            ->whereNotNull('path')
            ->orderBy('id');

        $total = $query->count();

        if ($total === 0) {
            $this->components->info('No stored post images found.');

            return self::SUCCESS;
        }

        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        $query->each(function (PostAsset $asset) use ($responsiveImage, $progressBar, &$processed, &$skipped, &$failed, &$failedAssetIds): void {
            $metadata = $asset->metadata ?? [];

            if (! $this->option('force') && $this->hasResponsiveSources($metadata['responsive_image'] ?? null)) {
                $skipped++;
                $progressBar->advance();

                return;
            }

            $generated = $responsiveImage->generate($asset->disk ?? 'public', $asset->path);

            if ($generated === null) {
                $failed++;
                $failedAssetIds[] = $asset->id;
                $progressBar->advance();

                return;
            }

            $asset->update([
                'metadata' => array_replace($metadata, [
                    'responsive_image' => $generated,
                ]),
            ]);

            $processed++;
            $progressBar->advance();
        });

        $progressBar->finish();
        $this->newLine(2);

        $this->components->info("Generated responsive variants for {$processed} post asset(s).");

        if ($skipped > 0) {
            $this->components->info("Skipped {$skipped} post asset(s) that already had responsive variants.");
        }

        if ($failed > 0) {
            $this->components->warn("Failed to generate responsive variants for {$failed} post asset(s).");
            $this->components->warn('Failed post asset IDs: '.implode(', ', $failedAssetIds));
        }

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function hasResponsiveSources(mixed $responsiveImage): bool
    {
        return is_array($responsiveImage)
            && is_array($responsiveImage['sources'] ?? null)
            && collect($responsiveImage['sources'])->flatten(1)->isNotEmpty();
    }
}
