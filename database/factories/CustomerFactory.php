<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
final class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'birth_date' => fake()->dateTimeBetween('-70 years', '-16 years'),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'postal_code' => fake()->postcode(),
            'is_loyalty_member' => fake()->boolean(60), // 60% chance of being loyalty member
            'loyalty_points' => fake()->numberBetween(0, 2000),
            'preferences' => [
                'drink_type' => fake()->randomElement(['coffee', 'tea', 'both']),
                'milk_preference' => fake()->randomElement(['whole', 'skim', 'oat', 'almond', 'soy']),
                'sweetness_level' => fake()->randomElement(['no sugar', 'light', 'regular', 'extra']),
                'temperature' => fake()->randomElement(['hot', 'ice']),
            ],
            'allergies' => fake()->boolean(20) ? fake()->randomElements(['nuts', 'dairy', 'gluten', 'soy'], fake()->numberBetween(1, 2)) : null,
            'is_active' => true,
            'last_visit_at' => fake()->dateTimeBetween('-3 months', 'now'),
        ];
    }
}
