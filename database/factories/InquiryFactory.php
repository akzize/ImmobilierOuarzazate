<?php

namespace Database\Factories;

use App\Enums\InquirySource;
use App\Enums\InquiryStatus;
use App\Enums\Language;
use App\Models\Inquiry;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inquiry>
 */
class InquiryFactory extends Factory
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
            'full_name' => fake()->name(),
            'phone' => fake()->numerify('06########'),
            'email' => fake()->optional()->safeEmail(),
            'message' => fake()->paragraph(),
            'lang' => Language::Arabic,
            'source' => InquirySource::Form,
            'status' => InquiryStatus::New,
            'admin_notes' => null,
        ];
    }

    public function general(): static
    {
        return $this->state(fn (array $attributes) => [
            'property_id' => null,
        ]);
    }
}
