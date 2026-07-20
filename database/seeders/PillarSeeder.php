<?php

namespace Database\Seeders;

use App\Enums\Language;
use App\Models\Pillar;
use Illuminate\Database\Seeder;

class PillarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        collect([
            [
                'key' => 'bitcoin-money',
                'translations' => [
                    Language::English->value => [
                        'slug' => 'bitcoin-money',
                        'name' => 'Bitcoin & Money',
                        'description' => 'Practical, long-term guidance for understanding Bitcoin, using it independently, and making considered decisions about money.',
                    ],
                    Language::German->value => [
                        'slug' => 'bitcoin-geld',
                        'name' => 'Bitcoin & Geld',
                        'description' => 'Praktische, langfristige Orientierung, um Bitcoin zu verstehen, selbstständig zu nutzen und über Geld bewusst zu entscheiden.',
                    ],
                ],
            ],
            [
                'key' => 'digital-sovereignty',
                'translations' => [
                    Language::English->value => [
                        'slug' => 'digital-sovereignty',
                        'name' => 'Digital Sovereignty',
                        'description' => 'Clear guidance for protecting privacy, securing digital tools, and retaining control over personal data and communication.',
                    ],
                    Language::German->value => [
                        'slug' => 'digitale-souveraenitaet',
                        'name' => 'Digitale Souveränität',
                        'description' => 'Klare Orientierung, um Privatsphäre zu schützen, digitale Werkzeuge abzusichern und die Kontrolle über Daten und Kommunikation zu behalten.',
                    ],
                ],
            ],
            [
                'key' => 'decisions-preparedness',
                'translations' => [
                    Language::English->value => [
                        'slug' => 'decisions-preparedness',
                        'name' => 'Decisions & Preparedness',
                        'description' => 'Practical frameworks for thoughtful decisions, documentation, long-term planning, and preparing the people who depend on you.',
                    ],
                    Language::German->value => [
                        'slug' => 'entscheiden-vorsorgen',
                        'name' => 'Entscheiden & Vorsorgen',
                        'description' => 'Praktische Denkmodelle für bewusste Entscheidungen, Dokumentation, langfristige Planung und Vorsorge für die Menschen, die dir wichtig sind.',
                    ],
                ],
            ],
        ])->each(function (array $pillar): void {
            collect($pillar['translations'])->each(function (array $translation, string $lang) use ($pillar): void {
                Pillar::query()->updateOrCreate(
                    [
                        'key' => $pillar['key'],
                        'lang' => $lang,
                    ],
                    [
                        'slug' => $translation['slug'],
                        'name' => $translation['name'],
                        'description' => $translation['description'],
                    ],
                );
            });
        });
    }
}
