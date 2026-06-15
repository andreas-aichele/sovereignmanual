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
                'slug' => 'self-custody',
                'name' => ['en' => 'Self Custody', 'de' => 'Selbstverwahrung'],
            ],
            [
                'slug' => 'privacy-security',
                'name' => ['en' => 'Privacy & Security', 'de' => 'Privatsphäre & Sicherheit'],
            ],
            [
                'slug' => 'financial-sovereignty',
                'name' => ['en' => 'Financial Sovereignty', 'de' => 'Finanzielle Souveränität'],
            ],
            [
                'slug' => 'family-legacy',
                'name' => ['en' => 'Family & Legacy', 'de' => 'Familie & Nachlass'],
            ],
            [
                'slug' => 'tools-practice',
                'name' => ['en' => 'Tools & Practice', 'de' => 'Werkzeuge & Praxis'],
            ],
            [
                'slug' => 'economics',
                'name' => ['en' => 'Economics', 'de' => 'Ökonomie'],
            ],
            [
                'slug' => 'mindset',
                'name' => ['en' => 'Mindset', 'de' => 'Denkweise'],
            ],
        ])->each(function (array $category): void {
            Category::query()->updateOrCreate(
                ['slug' => $category['slug']],
                ['name' => $category['name']],
            );
        });
    }
}
