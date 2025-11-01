<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Ingredient;
use App\Services\InventoryService;

final readonly class AdjustInventoryAction
{
    public function __construct(
        private InventoryService $inventoryService,
    ) {}

    /**
     * @return array{success: bool, message: string, ingredient_name: string, previous_stock?: float, new_stock?: float}
     */
    public function execute(int $ingredientId, float $newQuantity, string $reason): array
    {
        $ingredient = Ingredient::query()->find($ingredientId);

        if (! $ingredient) {
            return [
                'success' => false,
                'message' => 'Ingredient not found',
                'ingredient_name' => '',
            ];
        }

        if ($this->inventoryService->adjustIngredientStock($ingredient, $newQuantity, $reason)) {
            $inventory = $ingredient->inventory;
            $previousStock = $inventory instanceof \App\Models\IngredientInventory
                ? (float) $inventory->current_stock
                : 0.0;

            return [
                'success' => true,
                'message' => 'Inventory adjusted successfully',
                'ingredient_name' => $ingredient->name,
                'previous_stock' => $previousStock,
                'new_stock' => $newQuantity,
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to adjust inventory',
            'ingredient_name' => $ingredient->name,
        ];
    }

    /**
     * @return array{success: bool, message: string, ingredient_name: string, quantity_added?: float, new_stock?: float}
     */
    public function restock(int $ingredientId, float $quantity, string $reason): array
    {
        $ingredient = Ingredient::query()->find($ingredientId);

        if (! $ingredient) {
            return [
                'success' => false,
                'message' => 'Ingredient not found',
                'ingredient_name' => '',
            ];
        }

        if ($this->inventoryService->restockIngredient($ingredient, $quantity, $reason)) {
            $inventory = $ingredient->inventory;
            $newStock = $inventory instanceof \App\Models\IngredientInventory
                ? (float) $inventory->current_stock
                : 0.0;

            return [
                'success' => true,
                'message' => 'Ingredient restocked successfully',
                'ingredient_name' => $ingredient->name,
                'quantity_added' => $quantity,
                'new_stock' => $newStock,
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to restock ingredient',
            'ingredient_name' => $ingredient->name,
        ];
    }

    /**
     * @return array{success: bool, message: string, ingredient_name: string, waste_quantity?: float, remaining_stock?: float}
     */
    public function recordWaste(int $ingredientId, float $quantity, string $reason): array
    {
        $ingredient = Ingredient::query()->find($ingredientId);

        if (! $ingredient) {
            return [
                'success' => false,
                'message' => 'Ingredient not found',
                'ingredient_name' => '',
            ];
        }

        if ($this->inventoryService->recordWaste($ingredient, $quantity, $reason)) {
            $inventory = $ingredient->inventory;
            $remainingStock = $inventory instanceof \App\Models\IngredientInventory
                ? (float) $inventory->current_stock
                : 0.0;

            return [
                'success' => true,
                'message' => 'Waste recorded successfully',
                'ingredient_name' => $ingredient->name,
                'waste_quantity' => $quantity,
                'remaining_stock' => $remainingStock,
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to record waste or insufficient stock',
            'ingredient_name' => $ingredient->name,
        ];
    }
}
