<?php

namespace Database\Seeders;

use App\Enums\Language;
use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        collect([
            [
                'key' => 'self-custody',
                'translations' => [
                    Language::English->value => [
                        'slug' => 'self-custody',
                        'name' => 'Self Custody',
                        'description' => 'Guides for holding your own keys, planning recovery, and reducing custody risk without depending on custodians.',
                    ],
                    Language::German->value => [
                        'slug' => 'selbstverwahrung',
                        'name' => 'Selbstverwahrung',
                        'description' => 'Anleitungen für eigene Schlüssel, Wiederherstellungspläne und geringere Verwahrungsrisiken ohne Abhängigkeit von Verwahrern.',
                    ],
                ],
            ],
            [
                'key' => 'privacy-security',
                'translations' => [
                    Language::English->value => [
                        'slug' => 'privacy-security',
                        'name' => 'Privacy & Security',
                        'description' => 'Operational security, privacy habits, and threat models for using Bitcoin with less exposure.',
                    ],
                    Language::German->value => [
                        'slug' => 'privatsphaere-sicherheit',
                        'name' => 'Privatsphäre & Sicherheit',
                        'description' => 'Operative Sicherheit, Datenschutz-Routinen und Bedrohungsmodelle für weniger Angriffsfläche beim Umgang mit Bitcoin.',
                    ],
                ],
            ],
            [
                'key' => 'financial-sovereignty',
                'translations' => [
                    Language::English->value => [
                        'slug' => 'financial-sovereignty',
                        'name' => 'Financial Sovereignty',
                        'description' => 'Frameworks for saving, spending, and making independent financial decisions in a Bitcoin context.',
                    ],
                    Language::German->value => [
                        'slug' => 'finanzielle-souveraenitaet',
                        'name' => 'Finanzielle Souveränität',
                        'description' => 'Denkmodelle für Sparen, Ausgeben und unabhängige finanzielle Entscheidungen im Bitcoin-Kontext.',
                    ],
                ],
            ],
            [
                'key' => 'family-legacy',
                'translations' => [
                    Language::English->value => [
                        'slug' => 'family-legacy',
                        'name' => 'Family & Legacy',
                        'description' => 'Estate planning, family education, and practical continuity for long-term Bitcoin ownership.',
                    ],
                    Language::German->value => [
                        'slug' => 'familie-nachlass',
                        'name' => 'Familie & Nachlass',
                        'description' => 'Nachlassplanung, Familienbildung und praktische Kontinuität für langfristigen Bitcoin-Besitz.',
                    ],
                ],
            ],
            [
                'key' => 'tools-practice',
                'translations' => [
                    Language::English->value => [
                        'slug' => 'tools-practice',
                        'name' => 'Tools & Practice',
                        'description' => 'Hands-on workflows, wallet tools, checklists, and routines that make Bitcoin usage more reliable.',
                    ],
                    Language::German->value => [
                        'slug' => 'werkzeuge-praxis',
                        'name' => 'Werkzeuge & Praxis',
                        'description' => 'Praktische Abläufe, Wallet-Werkzeuge, Checklisten und Routinen für verlässlichere Bitcoin-Nutzung.',
                    ],
                ],
            ],
            [
                'key' => 'economics',
                'translations' => [
                    Language::English->value => [
                        'slug' => 'economics',
                        'name' => 'Economics',
                        'description' => 'Monetary history, incentives, scarcity, and economic thinking behind Bitcoin.',
                    ],
                    Language::German->value => [
                        'slug' => 'oekonomie',
                        'name' => 'Ökonomie',
                        'description' => 'Geldgeschichte, Anreize, Knappheit und ökonomisches Denken hinter Bitcoin.',
                    ],
                ],
            ],
            [
                'key' => 'mindset',
                'translations' => [
                    Language::English->value => [
                        'slug' => 'mindset',
                        'name' => 'Mindset',
                        'description' => 'Mental models, discipline, patience, and personal responsibility for living with Bitcoin.',
                    ],
                    Language::German->value => [
                        'slug' => 'denkweise',
                        'name' => 'Denkweise',
                        'description' => 'Denkmodelle, Disziplin, Geduld und Eigenverantwortung für ein Leben mit Bitcoin.',
                    ],
                ],
            ],
        ])->each(function (array $category): void {
            collect($category['translations'])->each(function (array $translation, string $lang) use ($category): void {
                Category::query()->updateOrCreate(
                    [
                        'key' => $category['key'],
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
