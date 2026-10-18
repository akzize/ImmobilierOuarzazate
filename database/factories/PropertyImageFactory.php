<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertyImage>
 */
class PropertyImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'path' => 'properties/'.fake()->uuid().'.jpg',
            'position' => 0,
            'is_cover' => false,
            'alt_text' => fake()->sentence(3),
        ];
    }

    public function cover(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_cover' => true,
        ]);
    }
}
