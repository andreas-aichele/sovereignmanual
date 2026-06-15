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

        return [
            'slug' => Str::slug($name),
            'name' => [
                'en' => Str::of($name)->title()->toString(),
                'de' => Str::of($name)->title()->toString(),
            ],
        ];
    }

    public function selfCustody(): static
    {
        return $this->state(fn (array $attributes): array => [
            'slug' => 'self-custody',
            'name' => [
                'en' => 'Self Custody',
                'de' => 'Selbstverwahrung',
            ],
        ]);
    }
}
