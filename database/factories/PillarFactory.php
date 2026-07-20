<?php

namespace Database\Factories;

use App\Enums\Language;
use App\Models\Pillar;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Pillar>
 */
class PillarFactory extends Factory
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
            'lang' => Language::English,
            'slug' => $slug,
            'name' => Str::of($name)->title()->toString(),
            'description' => fake()->sentence(),
        ];
    }
}
