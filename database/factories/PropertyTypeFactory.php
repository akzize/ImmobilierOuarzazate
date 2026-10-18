<?php

namespace Database\Factories;

use App\Enums\PropertyGroup;
use App\Models\PropertyType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertyType>
 */
class PropertyTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('??????'),
            'group' => fake()->randomElement(PropertyGroup::cases()),
            'label_ar' => fake()->word(),
            'label_en' => fake()->unique()->word(),
            'position' => 0,
        ];
    }
}
