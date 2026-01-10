<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;

final class DrinkCostingService
{
    /**
     * Default labor percentage (30%)
     */
    public const float DEFAULT_LABOR_PERCENTAGE = 30.0;

    /**
     * Default markup percentage (40%)
     */
    public const float DEFAULT_MARKUP_PERCENTAGE = 40.0;

    /**
     * Get all drink products with costing calculations
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getDrinkCostings(
        ?int $categoryId = null,
        float $laborPercentage = self::DEFAULT_LABOR_PERCENTAGE,
        float $markupPercentage = self::DEFAULT_MARKUP_PERCENTAGE
    ): Collection {
        $query = Product::query()
            ->with(['category', 'ingredients.ingredient.inventory'])
            ->whereHas('category', function ($q): void {
                // Filter for beverage/drink categories
                $q->where('is_active', true);
            })
            ->where('is_active', true);

        if ($categoryId !== null) {
            $query->where('category_id', $categoryId);
        }

        return $query->get()->map(function (Product $product) use ($laborPercentage, $markupPercentage): array {
            return $this->calculateProductCosting($product, $laborPercentage, $markupPercentage);
        });
    }

    /**
     * Calculate costing for a single product
     *
     * @return array<string, mixed>
     */
    public function calculateProductCosting(
        Product $product,
        float $laborPercentage = self::DEFAULT_LABOR_PERCENTAGE,
        float $markupPercentage = self::DEFAULT_MARKUP_PERCENTAGE
    ): array {
        // Calculate ingredient cost (subtotal)
        $ingredientCost = $this->calculateIngredientCost($product);

        // Calculate labor cost (subtotal × labor percentage)
        $laborCost = $ingredientCost * ($laborPercentage / 100);

        // Calculate total cost (ingredient cost + labor)
        $totalCost = $ingredientCost + $laborCost;

        // Menu price from product
        $menuPrice = (float) $product->price;

        // Calculate the actual markup amount based on total cost
        $markupAmount = $totalCost * ($markupPercentage / 100);

        // Suggested price based on cost + markup
        $suggestedPrice = $totalCost + $markupAmount;

        // Calculate gross profit (menu price - total cost)
        $grossProfit = $menuPrice - $totalCost;

        // Calculate profit margin percentage
        $profitMargin = $menuPrice > 0 ? ($grossProfit / $menuPrice) * 100 : 0;

        // Calculate actual markup percentage based on cost
        $actualMarkup = $totalCost > 0 ? (($menuPrice - $totalCost) / $totalCost) * 100 : 0;

        // Calculate food cost percentage (total cost / menu price × 100)
        $foodCostPercentage = $menuPrice > 0 ? ($totalCost / $menuPrice) * 100 : 0;

        // Get ingredient breakdown
        $ingredientBreakdown = $this->getIngredientBreakdown($product);

        return [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'category_name' => $product->category?->name ?? 'Uncategorized',
            'sku' => $product->sku,
            'ingredients' => $ingredientBreakdown,
            'ingredient_cost' => round($ingredientCost, 2),
            'labor_percentage' => $laborPercentage,
            'labor_cost' => round($laborCost, 2),
            'total_cost' => round($totalCost, 2),
            'menu_price' => round($menuPrice, 2),
            'markup_percentage' => $markupPercentage,
            'markup_amount' => round($markupAmount, 2),
            'suggested_price' => round($suggestedPrice, 2),
            'gross_profit' => round($grossProfit, 2),
            'profit_margin' => round($profitMargin, 2),
            'actual_markup' => round($actualMarkup, 2),
            'food_cost_percentage' => round($foodCostPercentage, 2),
            'is_profitable' => $grossProfit > 0,
            'price_status' => $this->getPriceStatus($menuPrice, $suggestedPrice),
        ];
    }

    /**
     * Calculate total ingredient cost for a product
     */
    public function calculateIngredientCost(Product $product): float
    {
        $totalCost = 0.0;

        foreach ($product->ingredients as $productIngredient) {
            $ingredient = $productIngredient->ingredient;
            if ($ingredient === null) {
                continue;
            }

            $quantityRequired = (float) $productIngredient->quantity_required;
            $unitCost = (float) ($ingredient->inventory?->unit_cost ?? 0);

            $totalCost += $quantityRequired * $unitCost;
        }

        return $totalCost;
    }

    /**
     * Get detailed ingredient breakdown for a product
     *
     * @return array<int, array<string, mixed>>
     */
    public function getIngredientBreakdown(Product $product): array
    {
        $breakdown = [];

        foreach ($product->ingredients as $productIngredient) {
            $ingredient = $productIngredient->ingredient;
            if ($ingredient === null) {
                continue;
            }

            $quantityRequired = (float) $productIngredient->quantity_required;
            $unitCost = (float) ($ingredient->inventory?->unit_cost ?? 0);
            $itemCost = $quantityRequired * $unitCost;

            $breakdown[] = [
                'ingredient_id' => $ingredient->id,
                'ingredient_name' => $ingredient->name,
                'quantity_required' => $quantityRequired,
                'unit_type' => $ingredient->unit_type?->value ?? 'units',
                'unit_cost' => round($unitCost, 4),
                'item_cost' => round($itemCost, 2),
            ];
        }

        return $breakdown;
    }

    /**
     * Get summary statistics for all drink products
     *
     * @param  Collection<int, array<string, mixed>>  $costings
     * @return array<string, mixed>
     */
    public function getSummaryStatistics(Collection $costings): array
    {
        if ($costings->isEmpty()) {
            return [
                'total_products' => 0,
                'avg_ingredient_cost' => 0,
                'avg_labor_cost' => 0,
                'avg_total_cost' => 0,
                'avg_menu_price' => 0,
                'avg_gross_profit' => 0,
                'avg_profit_margin' => 0,
                'avg_food_cost_percentage' => 0,
                'profitable_count' => 0,
                'unprofitable_count' => 0,
                'above_target_count' => 0,
                'on_target_count' => 0,
                'below_target_count' => 0,
            ];
        }

        return [
            'total_products' => $costings->count(),
            'avg_ingredient_cost' => round($costings->avg('ingredient_cost'), 2),
            'avg_labor_cost' => round($costings->avg('labor_cost'), 2),
            'avg_total_cost' => round($costings->avg('total_cost'), 2),
            'avg_menu_price' => round($costings->avg('menu_price'), 2),
            'avg_gross_profit' => round($costings->avg('gross_profit'), 2),
            'avg_profit_margin' => round($costings->avg('profit_margin'), 2),
            'avg_food_cost_percentage' => round($costings->avg('food_cost_percentage'), 2),
            'profitable_count' => $costings->where('is_profitable', true)->count(),
            'unprofitable_count' => $costings->where('is_profitable', false)->count(),
            'above_target_count' => $costings->where('price_status', 'above_target')->count(),
            'on_target_count' => $costings->where('price_status', 'on_target')->count(),
            'below_target_count' => $costings->where('price_status', 'below_target')->count(),
        ];
    }

    /**
     * Get all active categories for filtering
     *
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get price status based on menu price vs suggested price
     */
    private function getPriceStatus(float $menuPrice, float $suggestedPrice): string
    {
        $difference = $menuPrice - $suggestedPrice;
        $percentageDiff = $suggestedPrice > 0 ? ($difference / $suggestedPrice) * 100 : 0;

        if ($percentageDiff >= 10) {
            return 'above_target';
        }

        if ($percentageDiff <= -10) {
            return 'below_target';
        }

        return 'on_target';
    }
}
