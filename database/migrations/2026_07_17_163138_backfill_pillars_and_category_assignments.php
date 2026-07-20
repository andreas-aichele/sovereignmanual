<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('pillars') || ! Schema::hasTable('categories')) {
            return;
        }

        $timestamp = now();
        $pillars = [
            [
                'key' => 'bitcoin-money',
                'translations' => [
                    'en' => [
                        'slug' => 'bitcoin-money',
                        'name' => 'Bitcoin & Money',
                        'description' => 'Practical, long-term guidance for understanding Bitcoin, using it independently, and making considered decisions about money.',
                    ],
                    'de' => [
                        'slug' => 'bitcoin-geld',
                        'name' => 'Bitcoin & Geld',
                        'description' => 'Praktische, langfristige Orientierung, um Bitcoin zu verstehen, selbstständig zu nutzen und über Geld bewusst zu entscheiden.',
                    ],
                ],
            ],
            [
                'key' => 'digital-sovereignty',
                'translations' => [
                    'en' => [
                        'slug' => 'digital-sovereignty',
                        'name' => 'Digital Sovereignty',
                        'description' => 'Clear guidance for protecting privacy, securing digital tools, and retaining control over personal data and communication.',
                    ],
                    'de' => [
                        'slug' => 'digitale-souveraenitaet',
                        'name' => 'Digitale Souveränität',
                        'description' => 'Klare Orientierung, um Privatsphäre zu schützen, digitale Werkzeuge abzusichern und die Kontrolle über Daten und Kommunikation zu behalten.',
                    ],
                ],
            ],
            [
                'key' => 'decisions-preparedness',
                'translations' => [
                    'en' => [
                        'slug' => 'decisions-preparedness',
                        'name' => 'Decisions & Preparedness',
                        'description' => 'Practical frameworks for thoughtful decisions, documentation, long-term planning, and preparing the people who depend on you.',
                    ],
                    'de' => [
                        'slug' => 'entscheiden-vorsorgen',
                        'name' => 'Entscheiden & Vorsorgen',
                        'description' => 'Praktische Denkmodelle für bewusste Entscheidungen, Dokumentation, langfristige Planung und Vorsorge für die Menschen, die dir wichtig sind.',
                    ],
                ],
            ],
        ];

        $rows = collect($pillars)
            ->flatMap(fn (array $pillar): array => collect($pillar['translations'])
                ->map(fn (array $translation, string $lang): array => [
                    'key' => $pillar['key'],
                    'lang' => $lang,
                    'slug' => $translation['slug'],
                    'name' => $translation['name'],
                    'description' => $translation['description'],
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ])
                ->values()
                ->all())
            ->all();

        DB::table('pillars')->upsert(
            $rows,
            ['key', 'lang'],
            ['slug', 'name', 'description', 'updated_at'],
        );

        $pillarKeys = [
            'self-custody' => 'bitcoin-money',
            'financial-sovereignty' => 'bitcoin-money',
            'economics' => 'bitcoin-money',
            'news' => 'bitcoin-money',
            'tools-practice' => 'bitcoin-money',
            'privacy-security' => 'digital-sovereignty',
            'mindset' => 'decisions-preparedness',
            'family-legacy' => 'decisions-preparedness',
        ];

        foreach ($pillarKeys as $categoryKey => $pillarKey) {
            $pillarIdsByLanguage = DB::table('pillars')
                ->where('key', $pillarKey)
                ->pluck('id', 'lang');

            foreach ($pillarIdsByLanguage as $lang => $pillarId) {
                DB::table('categories')
                    ->where('key', $categoryKey)
                    ->where('lang', $lang)
                    ->whereNull('pillar_id')
                    ->update([
                        'pillar_id' => $pillarId,
                        'updated_at' => $timestamp,
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Existing pillar assignments may have been edited after this migration.
    }
};
