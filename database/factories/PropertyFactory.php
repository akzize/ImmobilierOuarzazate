<?php

namespace Database\Factories;

use App\Enums\PropertyStatus;
use App\Enums\TransactionType;
use App\Models\City;
use App\Models\Property;
use App\Models\PropertyType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'property_type_id' => PropertyType::factory(),
            'city_id' => City::factory(),
            'transaction' => TransactionType::Sale,
            'status' => PropertyStatus::Draft,
            'featured' => false,
            'title_ar' => fake()->sentence(4),
            'title_en' => null,
            'description_ar' => fake()->paragraphs(2, true),
            'description_en' => null,
            'price' => fake()->numberBetween(200, 5000) * 1000,
            'price_negotiable' => fake()->boolean(),
            'surface_m2' => fake()->numberBetween(50, 1000),
        ];
    }

    public function withEnglish(): static
    {
        return $this->state(fn (array $attributes) => [
            'title_en' => fake()->sentence(4),
            'description_en' => fake()->paragraphs(2, true),
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PropertyStatus::Published,
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'featured' => true,
        ]);
    }

    public function forRent(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction' => TransactionType::Rent,
            'price' => fake()->numberBetween(2, 20) * 1000,
        ]);
    }
}
