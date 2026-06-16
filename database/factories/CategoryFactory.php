<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);
        $slug = Str::slug($name);

        return [
            'key' => $slug,
            'lang' => 'en',
            'slug' => $slug,
            'name' => Str::of($name)->title()->toString(),
            'description' => fake()->sentence(),
        ];
    }

    public function selfCustody(): static
    {
        return $this->state(fn (array $attributes): array => [
            'key' => 'self-custody',
            'lang' => 'en',
            'slug' => 'self-custody',
            'name' => 'Self Custody',
            'description' => 'Practical guidance for holding keys, building recovery plans, and reducing custody risk.',
        ]);
    }

    public function selbstverwahrung(): static
    {
        return $this->state(fn (array $attributes): array => [
            'key' => 'self-custody',
            'lang' => 'de',
            'slug' => 'selbstverwahrung',
            'name' => 'Selbstverwahrung',
            'description' => 'Praktische Orientierung für eigene Schlüssel, Wiederherstellungspläne und geringere Verwahrungsrisiken.',
        ]);
    }
}
