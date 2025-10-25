<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Ingredient;
use App\Services\InventoryService;

final class AdjustInventoryAction
{
    public function __construct(
        private InventoryService $inventoryService,
    ) {}

    public function execute(int $ingredientId, float $newQuantity, string $reason): array
    {
        $ingredient = Ingredient::find($ingredientId);

        if (! $ingredient) {
            return [
                'success' => false,
                'message' => 'Ingredient not found',
            ];
        }

        if ($this->inventoryService->adjustIngredientStock($ingredient, $newQuantity, $reason)) {
            return [
                'success' => true,
                'message' => 'Inventory adjusted successfully',
                'ingredient_name' => $ingredient->name,
                'previous_stock' => $ingredient->current_stock,
                'new_stock' => $newQuantity,
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to adjust inventory',
            'ingredient_name' => $ingredient->name,
        ];
    }

    public function restock(int $ingredientId, float $quantity, string $reason): array
    {
        $ingredient = Ingredient::find($ingredientId);

        if (! $ingredient) {
            return [
                'success' => false,
                'message' => 'Ingredient not found',
            ];
        }

        if ($this->inventoryService->restockIngredient($ingredient, $quantity, $reason)) {
            $inventory = $ingredient->inventory()->first();

            return [
                'success' => true,
                'message' => 'Ingredient restocked successfully',
                'ingredient_name' => $ingredient->name,
                'quantity_added' => $quantity,
                'new_stock' => $inventory->current_stock,
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to restock ingredient',
            'ingredient_name' => $ingredient->name,
        ];
    }

    public function recordWaste(int $ingredientId, float $quantity, string $reason): array
    {
        $ingredient = Ingredient::find($ingredientId);

        if (! $ingredient) {
            return [
                'success' => false,
                'message' => 'Ingredient not found',
            ];
        }

        if ($this->inventoryService->recordWaste($ingredient, $quantity, $reason)) {
            $inventory = $ingredient->inventory()->first();

            return [
                'success' => true,
                'message' => 'Waste recorded successfully',
                'ingredient_name' => $ingredient->name,
                'waste_quantity' => $quantity,
                'remaining_stock' => $inventory->current_stock,
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to record waste or insufficient stock',
            'ingredient_name' => $ingredient->name,
        ];
    }
}
