<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UnitType;
use App\Models\Ingredient;
use App\Models\IngredientInventory;
use App\Models\InventoryTransaction;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductIngredient;
use Illuminate\Support\Collection;
use InvalidArgumentException;

final class InventoryService
{
    public function __construct(
        private readonly UnitConversionService $unitConversionService
    ) {}

    public function decreaseIngredientStock(Ingredient $ingredient, float $quantity, ?OrderItem $orderItem = null, ?string $reason = null): bool
    {
        /** @var IngredientInventory|null $inventory */
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
        /** @var IngredientInventory $inventory */
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
        /** @var IngredientInventory $inventory */
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
        /** @var IngredientInventory|null $inventory */
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
            /** @var IngredientInventory|null $inventory */
            $inventory = $ingredient->inventory()->first();
            $requiredQuantity = $productIngredient->quantity_required * $quantity;

            if (! $inventory || $inventory->current_stock < $requiredQuantity) {
                return false;
            }
        }

        return true;
    }

    /**
     * Deduct inventory for a product when it's ordered.
     * Handles unit conversion automatically (e.g., 250ml from inventory in liters).
     *
     * @param  Product|int  $product  The product or product ID
     * @param  int  $quantity  Number of products ordered
     * @param  OrderItem|null  $orderItem  Optional order item for tracking
     * @return bool True if successful, false if insufficient inventory
     */
    public function deductInventoryForProduct(Product|int $product, int $quantity = 1, ?OrderItem $orderItem = null): bool
    {
        // Load product if ID was provided
        if (is_int($product)) {
            $product = Product::query()->find($product);
            if (! $product) {
                return false;
            }
        }

        // Get all ingredients for the product
        $productIngredients = $product->ingredients()
            ->with('ingredient.inventory')
            ->get();

        // First, verify all ingredients are available
        foreach ($productIngredients as $productIngredient) {
            /** @var ProductIngredient $productIngredient */
            $ingredient = $productIngredient->ingredient;
            if (! $ingredient) {
                return false;
            }

            /** @var Ingredient $ingredient */
            $inventory = $ingredient->inventory;
            if (! $inventory) {
                return false; // No inventory tracking means we can't deduct
            }

            // Calculate required quantity (recipe quantity * number of products ordered)
            $requiredQuantity = $productIngredient->quantity_required * $quantity;

            // Get inventory unit type from the ingredient
            $inventoryUnitType = $ingredient->unit_type;

            // Smart detection: Determine input unit based on quantity magnitude
            // If >= 10, user likely input in small units (ml/g)
            // If < 10, user likely input in large units (L/kg)
            $inputUnitType = $this->detectInputUnit($requiredQuantity, $inventoryUnitType);

            // Normalize to inventory unit if different
            try {
                $normalizedQuantity = $this->unitConversionService->normalizeToInventoryUnit(
                    $requiredQuantity,
                    $inputUnitType,
                    $inventoryUnitType
                );
            } catch (InvalidArgumentException $e) {
                // Cannot convert units - fail the operation
                return false;
            }

            // Check if enough stock is available
            if ($inventory->current_stock < $normalizedQuantity) {
                return false;
            }
        }

        // All ingredients available - proceed with deduction
        foreach ($productIngredients as $productIngredient) {
            /** @var ProductIngredient $productIngredient */
            $ingredient = $productIngredient->ingredient;
            /** @var Ingredient $ingredient */
            $inventory = $ingredient->inventory;

            $requiredQuantity = $productIngredient->quantity_required * $quantity;
            $inventoryUnitType = $ingredient->unit_type;

            // Smart detection: Determine input unit based on quantity magnitude
            $inputUnitType = $this->detectInputUnit($requiredQuantity, $inventoryUnitType);

            // Normalize quantity
            $normalizedQuantity = $this->unitConversionService->normalizeToInventoryUnit(
                $requiredQuantity,
                $inputUnitType,
                $inventoryUnitType
            );

            // Deduct from inventory
            $this->decreaseIngredientStock(
                $ingredient,
                $normalizedQuantity,
                $orderItem,
                "Product order: {$product->name} (x{$quantity})"
            );
        }

        return true;
    }

    /**
     * Intelligently detect which unit the user likely meant based on the value magnitude.
     * For example: 250 likely means ml/g, while 0.25 likely means L/kg.
     */
    private function detectInputUnit(float $quantity, UnitType $inventoryUnitType): UnitType
    {
        return match ($inventoryUnitType) {
            // For volume: if >= 10, likely ml; if < 10, likely L
            UnitType::MILLILITERS, UnitType::LITERS => $quantity >= 10 ? UnitType::MILLILITERS : UnitType::LITERS,
            // For weight: if >= 10, likely g; if < 10, likely kg
            UnitType::GRAMS, UnitType::KILOGRAMS => $quantity >= 10 ? UnitType::GRAMS : UnitType::KILOGRAMS,
            // For pieces, use as-is
            default => $inventoryUnitType
        };
    }
}
