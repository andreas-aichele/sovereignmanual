<?php

namespace Database\Seeders;

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
                    'en' => [
                        'slug' => 'self-custody',
                        'name' => 'Self Custody',
                        'description' => 'Guides for holding your own keys, planning recovery, and reducing custody risk without depending on custodians.',
                    ],
                    'de' => [
                        'slug' => 'selbstverwahrung',
                        'name' => 'Selbstverwahrung',
                        'description' => 'Anleitungen für eigene Schlüssel, Wiederherstellungspläne und geringere Verwahrungsrisiken ohne Abhängigkeit von Verwahrern.',
                    ],
                ],
            ],
            [
                'key' => 'privacy-security',
                'translations' => [
                    'en' => [
                        'slug' => 'privacy-security',
                        'name' => 'Privacy & Security',
                        'description' => 'Operational security, privacy habits, and threat models for using Bitcoin with less exposure.',
                    ],
                    'de' => [
                        'slug' => 'privatsphaere-sicherheit',
                        'name' => 'Privatsphäre & Sicherheit',
                        'description' => 'Operative Sicherheit, Datenschutz-Routinen und Bedrohungsmodelle für weniger Angriffsfläche beim Umgang mit Bitcoin.',
                    ],
                ],
            ],
            [
                'key' => 'financial-sovereignty',
                'translations' => [
                    'en' => [
                        'slug' => 'financial-sovereignty',
                        'name' => 'Financial Sovereignty',
                        'description' => 'Frameworks for saving, spending, and making independent financial decisions in a Bitcoin context.',
                    ],
                    'de' => [
                        'slug' => 'finanzielle-souveraenitaet',
                        'name' => 'Finanzielle Souveränität',
                        'description' => 'Denkmodelle für Sparen, Ausgeben und unabhängige finanzielle Entscheidungen im Bitcoin-Kontext.',
                    ],
                ],
            ],
            [
                'key' => 'family-legacy',
                'translations' => [
                    'en' => [
                        'slug' => 'family-legacy',
                        'name' => 'Family & Legacy',
                        'description' => 'Estate planning, family education, and practical continuity for long-term Bitcoin ownership.',
                    ],
                    'de' => [
                        'slug' => 'familie-nachlass',
                        'name' => 'Familie & Nachlass',
                        'description' => 'Nachlassplanung, Familienbildung und praktische Kontinuität für langfristigen Bitcoin-Besitz.',
                    ],
                ],
            ],
            [
                'key' => 'tools-practice',
                'translations' => [
                    'en' => [
                        'slug' => 'tools-practice',
                        'name' => 'Tools & Practice',
                        'description' => 'Hands-on workflows, wallet tools, checklists, and routines that make Bitcoin usage more reliable.',
                    ],
                    'de' => [
                        'slug' => 'werkzeuge-praxis',
                        'name' => 'Werkzeuge & Praxis',
                        'description' => 'Praktische Abläufe, Wallet-Werkzeuge, Checklisten und Routinen für verlässlichere Bitcoin-Nutzung.',
                    ],
                ],
            ],
            [
                'key' => 'economics',
                'translations' => [
                    'en' => [
                        'slug' => 'economics',
                        'name' => 'Economics',
                        'description' => 'Monetary history, incentives, scarcity, and economic thinking behind Bitcoin.',
                    ],
                    'de' => [
                        'slug' => 'oekonomie',
                        'name' => 'Ökonomie',
                        'description' => 'Geldgeschichte, Anreize, Knappheit und ökonomisches Denken hinter Bitcoin.',
                    ],
                ],
            ],
            [
                'key' => 'mindset',
                'translations' => [
                    'en' => [
                        'slug' => 'mindset',
                        'name' => 'Mindset',
                        'description' => 'Mental models, discipline, patience, and personal responsibility for living with Bitcoin.',
                    ],
                    'de' => [
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
