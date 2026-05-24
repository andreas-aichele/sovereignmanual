<?php

namespace App\Http\Controllers;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\PostAsset;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(Request $request, string $locale = 'en'): Response
    {
        $locale = $locale === 'de' ? 'de' : 'en';

        $posts = Post::query()
            ->with(['translations', 'assets'])
            ->where('status', PostStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->limit(4)
            ->get()
            ->map(fn (Post $post): array => [
                'id' => $post->id,
                'title' => $post->translation($locale)?->title ?? $post->topic,
                'excerpt' => $post->translation($locale)?->excerpt,
                'url' => route($locale === 'de' ? 'blog.de.show' : 'blog.show', $post->translation($locale)?->slug ?? $post->slug),
                'image' => $this->coverImage($post)?->url,
                'image_alt' => $this->coverImage($post)?->alt_text,
                'audience_level' => $post->audience_level,
                'published_at' => $post->published_at?->toAtomString(),
            ]);

        return Inertia::render('Home', [
            'locale' => $locale,
            'featuredPost' => $posts->first(),
            'latestPosts' => $posts->skip(1)->values(),
            'copy' => $this->copy($locale),
            'meta' => [
                'title' => 'Sovereign Manual',
                'description' => $locale === 'de'
                    ? 'Ein Synthwave-Cypherpunk-Handbuch fuer Bitcoin, finanzielle Intelligenz und souveraene Unabhaengigkeit.'
                    : 'A synthwave cypherpunk field manual for Bitcoin, financial intelligence, and sovereign independence.',
            ],
        ]);
    }

    private function coverImage(Post $post): ?PostAsset
    {
        return $post->assets
            ->where('status', 'ready')
            ->first(fn ($asset): bool => ($asset->metadata['style'] ?? null) === 'synthwave-cypherpunk');
    }

    /**
     * @return array<string, mixed>
     */
    private function copy(string $locale): array
    {
        if ($locale === 'de') {
            return [
                'eyebrow' => 'Bitcoin-Souveraenitaet // Cypherpunk-Finance',
                'intro' => 'Ein Dark-Mode-Feldhandbuch fuer finanzielle Intelligenz, Unabhaengigkeit, Selbstverwahrung und Bitcoin-natives Denken.',
                'primaryCta' => 'Archiv betreten',
                'secondaryCta' => 'Ausgewaehlter Brief',
                'signalEyebrow' => 'Signalpfade',
                'signalTitle' => 'Lernen ohne Hype-Zyklus.',
                'topics' => [
                    ['title' => 'Selbstverwahrung', 'body' => 'Praktische Essays fuer robuste Entscheidungen in feindlichem monetaren Terrain.'],
                    ['title' => 'Fiat-Systeme', 'body' => 'Mechaniken verstehen, Risiken einordnen und langfristig denken.'],
                    ['title' => 'Souveraene Planung', 'body' => 'Routinen, Regeln und Modelle fuer finanzielle Handlungsfaehigkeit.'],
                ],
            ];
        }

        return [
            'eyebrow' => 'Bitcoin sovereignty // Cypherpunk finance',
            'intro' => 'A dark-mode field manual for financial intelligence, independence, self custody, and Bitcoin-native thinking.',
            'primaryCta' => 'Enter the archive',
            'secondaryCta' => 'Featured brief',
            'signalEyebrow' => 'Signal paths',
            'signalTitle' => 'Learn without the hype cycle.',
            'topics' => [
                ['title' => 'Self custody', 'body' => 'Practical essays for durable decisions in hostile monetary terrain.'],
                ['title' => 'Fiat systems', 'body' => 'Understand the mechanics, map the risks, and think in longer timeframes.'],
                ['title' => 'Sovereign planning', 'body' => 'Routines, rules, and models for durable financial agency.'],
            ],
        ];
    }
}
