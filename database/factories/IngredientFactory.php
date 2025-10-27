<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UnitType;
use App\Models\Ingredient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ingredient>
 */
final class IngredientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'unit_type' => fake()->randomElement(UnitType::cases())->value,
        ];
    }


}
