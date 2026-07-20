<?php

namespace Database\Seeders;

use App\Enums\Language;
use App\Models\Category;
use App\Models\Pillar;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(PillarSeeder::class);

        $pillarKeys = [
            'self-custody' => 'bitcoin-money',
            'financial-sovereignty' => 'bitcoin-money',
            'economics' => 'bitcoin-money',
            'news' => 'bitcoin-money',
            'privacy-security' => 'digital-sovereignty',
            'mindset' => 'decisions-preparedness',
            'family-legacy' => 'decisions-preparedness',
        ];

        collect([
            [
                'key' => 'self-custody',
                'translations' => [
                    Language::English->value => [
                        'slug' => 'self-custody',
                        'name' => 'Self Custody',
                        'description' => '**Not your keys, not your coins.**<br><br>Learn how to take full ownership of your Bitcoin by managing your own private keys. This category covers wallets, backups, seed phrases, hardware devices, recovery strategies, and common mistakes.<br><br>From your first withdrawal to advanced multi-signature setups, the goal is simple: reduce trust, increase resilience, and remain in control of your wealth.',
                    ],
                    Language::German->value => [
                        'slug' => 'selbstverwahrung',
                        'name' => 'Selbstverwahrung',
                        'description' => '**Nicht deine Schlüssel, nicht deine Bitcoin.**<br><br>Lerne, wie du die volle Kontrolle über dein Vermögen übernimmst, indem du deine privaten Schlüssel selbst verwahrst. In dieser Kategorie geht es um Wallets, Backups, Seed-Phrases, Hardware-Wallets, Wiederherstellungsstrategien und typische Fehlerquellen.<br><br>Vom ersten eigenen Wallet bis hin zu Multi-Signature-Lösungen steht ein Ziel im Mittelpunkt: weniger Vertrauen in Dritte und mehr Kontrolle über die eigenen Bitcoin.',
                    ],
                ],
            ],
            [
                'key' => 'privacy-security',
                'translations' => [
                    Language::English->value => [
                        'slug' => 'privacy-security',
                        'name' => 'Privacy & Security',
                        'description' => '**Security is a process, not a product.**<br><br>Explore practical privacy habits, secure communication, account protection, threat modeling, and risk management for the digital tools you use every day.<br><br>Understanding privacy and security helps reduce avoidable dependencies and keeps more of your data, communication, and money under your control.',
                    ],
                    Language::German->value => [
                        'slug' => 'privatsphaere-sicherheit',
                        'name' => 'Privatsphäre & Sicherheit',
                        'description' => '**Sicherheit ist kein Produkt, sondern ein Prozess.**<br><br>Hier findest du praktische Inhalte zu Datenschutz, sicherer Kommunikation, Kontoschutz, Bedrohungsmodellen und dem Umgang mit digitalen Risiken.<br><br>Wer Privatsphäre versteht und Sicherheitsmaßnahmen bewusst einsetzt, reduziert unnötige Abhängigkeiten und behält mehr Kontrolle über Daten, Kommunikation und Geld.',
                    ],
                ],
            ],
            [
                'key' => 'financial-sovereignty',
                'translations' => [
                    Language::English->value => [
                        'slug' => 'financial-sovereignty',
                        'name' => 'Financial Sovereignty',
                        'description' => '**Own your money. Own your future.**<br><br>Bitcoin changes the relationship between individuals and money. This category explains money, saving, self-custody, and long-term financial resilience without unnecessary intermediaries.<br><br>The focus is education and personal responsibility, never individual financial advice or quick-profit narratives.',
                    ],
                    Language::German->value => [
                        'slug' => 'finanzielle-souveraenitaet',
                        'name' => 'Finanzielle Souveränität',
                        'description' => '**Eigene Entscheidungen statt fremder Kontrolle.**<br><br>Bitcoin verändert die Beziehung zwischen Mensch und Geld. Diese Kategorie erklärt Geld, Sparen, Selbstverwahrung und langfristige finanzielle Resilienz ohne unnötige Vermittler.<br><br>Im Mittelpunkt stehen Bildung und Verantwortung, niemals individuelle Finanzberatung oder schnelle Gewinnversprechen.',
                    ],
                ],
            ],
            [
                'key' => 'family-legacy',
                'translations' => [
                    Language::English->value => [
                        'slug' => 'family-legacy',
                        'name' => 'Family & Legacy',
                        'description' => '**Important information should not live only in your head.**<br><br>Long-term responsibility includes clear documentation, careful conversations with family, and practical plans for the people who depend on you.<br><br>These articles offer education and preparation frameworks, not legal or inheritance advice.',
                    ],
                    Language::German->value => [
                        'slug' => 'familie-nachlass',
                        'name' => 'Familie & Nachlass',
                        'description' => '**Wichtige Informationen sollten nicht nur im eigenen Kopf liegen.**<br><br>Langfristige Verantwortung umfasst verständliche Dokumentation, gute Gespräche mit Angehörigen und praktische Pläne für Menschen, die auf dich zählen.<br><br>Diese Artikel bieten Orientierung und Vorsorge-Modelle, keine Rechts- oder Nachlassberatung.',
                    ],
                ],
            ],
            [
                'key' => 'economics',
                'translations' => [
                    Language::English->value => [
                        'slug' => 'economics',
                        'name' => 'Economics',
                        'description' => '**Money shapes society.**<br><br>Explore the economic ideas that underpin Bitcoin: monetary history, inflation, incentives, scarcity, market dynamics, and the evolution of money itself.<br><br>Understanding why Bitcoin exists often requires understanding the strengths and weaknesses of the systems it challenges.',
                    ],
                    Language::German->value => [
                        'slug' => 'oekonomie',
                        'name' => 'Ökonomie',
                        'description' => '**Geld prägt Gesellschaften.**<br><br>Diese Kategorie beschäftigt sich mit Geldgeschichte, Inflation, Anreizstrukturen, Knappheit und den ökonomischen Grundlagen von Bitcoin.<br><br>Wer verstehen möchte, warum Bitcoin entstanden ist, sollte auch die Stärken und Schwächen bestehender Geldsysteme verstehen.',
                    ],
                ],
            ],
            [
                'key' => 'mindset',
                'translations' => [
                    Language::English->value => [
                        'slug' => 'mindset',
                        'name' => 'Mindset',
                        'description' => '**Good decisions need room to breathe.**<br><br>Explore mental models, habits, and principles that make it easier to think in longer time horizons, document trade-offs, and take responsibility for everyday decisions.<br><br>Patience, humility, and continuous learning are more useful than certainty theater or market predictions.',
                    ],
                    Language::German->value => [
                        'slug' => 'denkweise',
                        'name' => 'Denkweise',
                        'description' => '**Gute Entscheidungen brauchen Luft zum Denken.**<br><br>Hier geht es um Denkmodelle, Gewohnheiten und Prinzipien, die helfen, langfristiger zu denken, Abwägungen zu dokumentieren und Verantwortung im Alltag zu übernehmen.<br><br>Geduld, Demut und kontinuierliches Lernen sind hilfreicher als falsche Gewissheit oder Marktprognosen.',
                    ],
                ],
            ],
            [
                'key' => 'news',
                'translations' => [
                    Language::English->value => [
                        'slug' => 'news',
                        'name' => 'News',
                        'description' => '**Stay informed without the noise.**<br><br>This historical category contains carefully sourced Bitcoin developments, including protocol work, regulation, and ecosystem changes.<br><br>New updates are published as selective briefings with sources, relevance, and uncertainty made clear.',
                    ],
                    Language::German->value => [
                        'slug' => 'news',
                        'name' => 'News',
                        'description' => '**Aktuelle Entwicklungen mit Kontext statt Schlagzeilen.**<br><br>Diese historische Kategorie bündelt sorgfältig belegte Entwicklungen aus dem Bitcoin-Ökosystem, etwa zu Protokoll, Regulierung und Infrastruktur.<br><br>Neue Updates erscheinen als selektive Briefings, die Quellen, Relevanz und offene Fragen sichtbar machen.',
                    ],
                ],
            ],
        ])->each(function (array $category) use ($pillarKeys): void {
            collect($category['translations'])->each(function (array $translation, string $lang) use ($category, $pillarKeys): void {
                Category::query()->updateOrCreate(
                    [
                        'key' => $category['key'],
                        'lang' => $lang,
                    ],
                    [
                        'slug' => $translation['slug'],
                        'name' => $translation['name'],
                        'description' => $translation['description'],
                        'pillar_id' => Pillar::query()
                            ->where('key', $pillarKeys[$category['key']])
                            ->where('lang', $lang)
                            ->value('id'),
                    ],
                );
            });
        });
    }
}
