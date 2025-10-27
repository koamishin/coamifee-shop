<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Ingredient;
use App\Models\IngredientInventory;
use App\Models\InventoryTransaction;
use App\Models\OrderItem;
use App\Models\ProductIngredient;
use Illuminate\Support\Collection;

final class InventoryService
{
    public function decreaseIngredientStock(Ingredient $ingredient, float $quantity, ?OrderItem $orderItem = null, ?string $reason = null): bool
    {
        $inventory = $ingredient->inventory()->first();
        if (! $inventory) {
            return true; // No inventory means can't track stock
        }

        $previousStock = $inventory->current_stock;
        $newStock = $previousStock - $quantity;

        if ($newStock < 0) {
            return false;
        }

        $inventory->update(['current_stock' => $newStock]);

        InventoryTransaction::query()->create([
            'ingredient_id' => $ingredient->id,
            'transaction_type' => 'usage',
            'quantity_change' => -$quantity,
            'previous_stock' => $previousStock,
            'new_stock' => $newStock,
            'reason' => $reason ?? 'Order processing',
            'order_item_id' => $orderItem?->id,
        ]);

        return true;
    }

    public function restockIngredient(Ingredient $ingredient, float $quantity, ?string $reason = null): bool
    {
        $inventory = $ingredient->inventory()->firstOrCreate([
            'ingredient_id' => $ingredient->id,
        ], [
            'current_stock' => 0,
            'min_stock_level' => 0,
            'max_stock_level' => null,
            'location' => 'Main Storage',
        ]);

        $previousStock = $inventory->current_stock;
        $newStock = $previousStock + $quantity;

        $inventory->update([
            'current_stock' => $newStock,
            'last_restocked_at' => now(),
        ]);

        InventoryTransaction::query()->create([
            'ingredient_id' => $ingredient->id,
            'transaction_type' => 'restock',
            'quantity_change' => $quantity,
            'previous_stock' => $previousStock,
            'new_stock' => $newStock,
            'reason' => $reason ?? 'Restock',
        ]);

        return true;
    }

    public function adjustIngredientStock(Ingredient $ingredient, float $newQuantity, ?string $reason = null): bool
    {
        $inventory = $ingredient->inventory()->firstOrCreate([
            'ingredient_id' => $ingredient->id,
        ], [
            'current_stock' => 0,
            'min_stock_level' => 0,
            'max_stock_level' => null,
            'location' => 'Main Storage',
        ]);

        $previousStock = $inventory->current_stock;
        $quantityChange = $newQuantity - $previousStock;

        $inventory->update(['current_stock' => $newQuantity]);

        InventoryTransaction::query()->create([
            'ingredient_id' => $ingredient->id,
            'transaction_type' => 'adjustment',
            'quantity_change' => $quantityChange,
            'previous_stock' => $previousStock,
            'new_stock' => $newQuantity,
            'reason' => $reason ?? 'Manual adjustment',
        ]);

        return true;
    }

    public function recordWaste(Ingredient $ingredient, float $quantity, ?string $reason = null): bool
    {
        $inventory = $ingredient->inventory()->first();
        if (! $inventory) {
            return false;
        }

        $previousStock = $inventory->current_stock;
        $newStock = $previousStock - $quantity;

        if ($newStock < 0) {
            return false;
        }

        $inventory->update(['current_stock' => $newStock]);

        InventoryTransaction::query()->create([
            'ingredient_id' => $ingredient->id,
            'transaction_type' => 'waste',
            'quantity_change' => -$quantity,
            'previous_stock' => $previousStock,
            'new_stock' => $newStock,
            'reason' => $reason ?? 'Waste recorded',
        ]);

        return true;
    }

    public function checkLowStock(): Collection
    {
        return IngredientInventory::with('ingredient')
            ->whereColumn('current_stock', '<=', 'min_stock_level')
            ->get();
    }

    public function getProductIngredients(int $productId): Collection
    {
        return ProductIngredient::with('ingredient')
            ->where('product_id', $productId)
            ->get();
    }

    public function canProduceProduct(int $productId, int $quantity = 1): bool
    {
        $productIngredients = $this->getProductIngredients($productId);

        foreach ($productIngredients as $productIngredient) {
            $ingredient = $productIngredient->ingredient;
            $inventory = $ingredient->inventory()->first();
            $requiredQuantity = $productIngredient->quantity_required * $quantity;

            if (! $inventory || $inventory->current_stock < $requiredQuantity) {
                return false;
            }
        }

        return true;
    }
}
