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
                        'description' => '**Security is a process, not a product.**<br><br>Protecting Bitcoin requires more than choosing a wallet. Explore practical operational security, privacy-preserving habits, secure communication, threat modeling, and risk management.<br><br>Whether you hold a small amount or significant wealth, understanding privacy and security helps reduce attack surfaces and maintain financial freedom.',
                    ],
                    Language::German->value => [
                        'slug' => 'privatsphaere-sicherheit',
                        'name' => 'Privatsphäre & Sicherheit',
                        'description' => '**Sicherheit ist kein Produkt, sondern ein Prozess.**<br><br>Der Schutz von Bitcoin endet nicht bei der Wahl einer Wallet. Hier findest du Inhalte zu operativer Sicherheit, Datenschutz, Bedrohungsmodellen, sicherer Kommunikation und dem Umgang mit Risiken.<br><br>Wer Privatsphäre versteht und Sicherheitsmaßnahmen bewusst einsetzt, schützt nicht nur seine Bitcoin, sondern auch seine persönliche Freiheit.',
                    ],
                ],
            ],
            [
                'key' => 'financial-sovereignty',
                'translations' => [
                    Language::English->value => [
                        'slug' => 'financial-sovereignty',
                        'name' => 'Financial Sovereignty',
                        'description' => '**Own your money. Own your future.**<br><br>Bitcoin changes the relationship between individuals and money. This category explores saving, spending, investing, and building long-term financial resilience without unnecessary intermediaries.<br><br>The focus is not on getting rich quickly, but on increasing independence, optionality, and personal responsibility.',
                    ],
                    Language::German->value => [
                        'slug' => 'finanzielle-souveraenitaet',
                        'name' => 'Finanzielle Souveränität',
                        'description' => '**Eigene Entscheidungen statt fremder Kontrolle.**<br><br>Bitcoin verändert die Beziehung zwischen Mensch und Geld. Diese Kategorie beleuchtet Sparen, Investieren, Konsum und langfristigen Vermögensaufbau aus einer souveränen Perspektive.<br><br>Im Mittelpunkt steht nicht die Jagd nach schnellen Gewinnen, sondern finanzielle Unabhängigkeit, Selbstbestimmung und Verantwortung für die eigenen Entscheidungen.',
                    ],
                ],
            ],
            [
                'key' => 'family-legacy',
                'translations' => [
                    Language::English->value => [
                        'slug' => 'family-legacy',
                        'name' => 'Family & Legacy',
                        'description' => '**Bitcoin should survive you.**<br><br>Long-term ownership requires planning beyond your own lifetime. Learn how to prepare your family, document recovery procedures, and create inheritance strategies that balance accessibility with security.<br><br>The objective is continuity: ensuring future generations can benefit from the wealth you preserve today.',
                    ],
                    Language::German->value => [
                        'slug' => 'familie-nachlass',
                        'name' => 'Familie & Nachlass',
                        'description' => '**Bitcoin sollte dich überdauern.**<br><br>Langfristige Selbstverwahrung endet nicht bei der eigenen Person. Erfahre, wie du Angehörige vorbereitest, Wiederherstellungsprozesse dokumentierst und einen sicheren Bitcoin-Nachlass planst.<br><br>Das Ziel ist Kontinuität: Vermögen für kommende Generationen erhalten, ohne dabei Sicherheit oder Privatsphäre zu opfern.',
                    ],
                ],
            ],
            [
                'key' => 'tools-practice',
                'translations' => [
                    Language::English->value => [
                        'slug' => 'tools-practice',
                        'name' => 'Tools & Practice',
                        'description' => '**Knowledge becomes valuable when applied.**<br><br>Discover practical guides, software, hardware, workflows, and checklists that help you use Bitcoin confidently in everyday life.<br><br>From wallet setup and node operation to backups and payment tools, this category turns theory into repeatable practice.',
                    ],
                    Language::German->value => [
                        'slug' => 'werkzeuge-praxis',
                        'name' => 'Werkzeuge & Praxis',
                        'description' => '**Wissen entfaltet seinen Wert erst in der Anwendung.**<br><br>Hier findest du praktische Anleitungen, Werkzeuge, Hardware-Empfehlungen, Checklisten und erprobte Abläufe für den Alltag mit Bitcoin.<br><br>Von Wallet-Einrichtungen über eigene Nodes bis hin zu Backup-Strategien wird Theorie in konkrete Praxis übersetzt.',
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
                        'description' => '**Bitcoin is as much a personal journey as a technological one.**<br><br>Explore the mental models, habits, and principles that help individuals think in longer time horizons and take greater responsibility for their decisions.<br><br>Patience, conviction, humility, and continuous learning are often more valuable than any market prediction.',
                    ],
                    Language::German->value => [
                        'slug' => 'denkweise',
                        'name' => 'Denkweise',
                        'description' => '**Bitcoin ist ebenso eine persönliche wie eine technologische Reise.**<br><br>Hier geht es um Denkmodelle, Gewohnheiten und Prinzipien, die helfen, langfristiger zu denken und Verantwortung für die eigenen Entscheidungen zu übernehmen.<br><br>Geduld, Überzeugung, Demut und kontinuierliches Lernen sind häufig wertvoller als jede Marktprognose.',
                    ],
                ],
            ],
            [
                'key' => 'news',
                'translations' => [
                    Language::English->value => [
                        'slug' => 'news',
                        'name' => 'News',
                        'description' => '**Stay informed without the noise.**<br><br>Follow important developments from the Bitcoin ecosystem, including regulation, institutional adoption, technological upgrades, industry events, and macroeconomic trends.<br><br>The focus is on relevance and context rather than headlines, helping readers understand what matters and why.',
                    ],
                    Language::German->value => [
                        'slug' => 'news',
                        'name' => 'News',
                        'description' => '**Aktuelle Entwicklungen mit Kontext statt Schlagzeilen.**<br><br>Hier findest du wichtige Neuigkeiten aus dem Bitcoin-Ökosystem: regulatorische Entwicklungen, technologische Fortschritte, Unternehmensmeldungen, Markttrends und bedeutende Ereignisse.<br><br>Der Fokus liegt darauf, Nachrichten einzuordnen und ihre langfristige Bedeutung für Bitcoin verständlich zu machen.',
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
