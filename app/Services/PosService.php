<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;

final class PosService
{
    public function __construct(
        private InventoryService $inventoryService,
        private ReportingService $reportingService
    ) {}

    /**
     * Get products filtered by category and search
     */
    public function getFilteredProducts(?int $selectedCategory = null, ?string $search = null): Collection
    {
        return Product::with(['category', 'ingredients.ingredient'])
            ->where('is_active', true)
            ->when($selectedCategory && $selectedCategory > 0, function ($query) use ($selectedCategory) {
                $query->where('category_id', $selectedCategory);
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%')
                        ->orWhereHas('category', function ($categoryQuery) use ($search) {
                            $categoryQuery->where('name', 'like', '%'.$search.'%');
                        });
                });
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Get all active categories
     */
    public function getActiveCategories(): Collection
    {
        return Category::where('is_active', true)->orderBy('name')->get();
    }

    /**
     * Get best selling products
     */
    public function getBestSellers(int $limit = 5, string $period = 'daily', int $days = 7): Collection
    {
        return $this->reportingService->getTopProducts($limit, $period, $days)
            ->map(fn ($metric) => $metric->product);
    }

    /**
     * Get quick add items (popular coffee items)
     */
    public function getQuickAddItems(int $limit = 8): array
    {
        return Product::where('is_active', true)
            ->whereHas('category', function ($query) {
                $query->whereIn('name', ['Coffee', 'Espresso', 'Latte']);
            })
            ->orderBy('name')
            ->take($limit)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'image' => $product->image_url,
                    'category' => $product->category->name,
                    'can_produce' => $this->inventoryService->canProduceProduct($product->id, 1),
                ];
            })
            ->toArray();
    }

    /**
     * Check if a product can be added to cart
     */
    public function canAddToCart(int $productId): bool
    {
        return $this->inventoryService->canProduceProduct($productId, 1);
    }

    /**
     * Get maximum producible quantity for a product
     */
    public function getMaxProducibleQuantity(int $productId): int
    {
        $product = Product::find($productId);
        if (! $product) {
            return 0;
        }

        $ingredients = $product->ingredients()->with('ingredient.inventory')->get();
        $maxQuantities = [];

        foreach ($ingredients as $productIngredient) {
            $ingredient = $productIngredient->ingredient;
            if ($ingredient->is_trackable) {
                $inventory = $ingredient->inventory;
                if ($inventory) {
                    $maxQuantities[] = (int) ($inventory->current_stock / $productIngredient->quantity_required);
                } else {
                    return 0; // No inventory means can't produce
                }
            }
        }

        return empty($maxQuantities) ? 999 : min($maxQuantities);
    }

    /**
     * Get stock status for a product
     */
    public function getStockStatus(int $productId): string
    {
        $canProduce = $this->inventoryService->canProduceProduct($productId, 1);
        $maxQuantity = $this->getMaxProducibleQuantity($productId);

        if (! $canProduce) {
            return 'out_of_stock';
        }
        if ($maxQuantity <= 5) {
            return 'low_stock';
        }

        return 'in_stock';
    }

    /**
     * Update product availability for a collection of products
     */
    public function updateProductAvailability(Collection $products): array
    {
        $availability = [];

        foreach ($products as $product) {
            $availability[$product->id] = [
                'can_produce' => $this->inventoryService->canProduceProduct($product->id, 1),
                'max_quantity' => $this->getMaxProducibleQuantity($product->id),
                'stock_status' => $this->getStockStatus($product->id),
            ];
        }

        return $availability;
    }

    /**
     * Get product availability for POS (includes cart-specific logic)
     */
    public function getPosProductAvailability(Collection $products, array $cart = []): array
    {
        $cartProductIds = [];
        foreach (array_keys($cart) as $cartKey) {
            // Extract base product ID from cart keys like "1_large_hot"
            $cartKey = (string) $cartKey;
            $parts = explode('_', $cartKey);
            $cartProductIds[] = (int) $parts[0];
        }

        $allProductIds = array_merge(
            $cartProductIds,
            $products->pluck('id')->toArray()
        );

        $availability = [];
        foreach ($allProductIds as $productId) {
            // Calculate total quantity in cart for this product (across all variants)
            $inCart = 0;
            foreach ($cart as $cartKey => $item) {
                if (str_starts_with((string) $cartKey, $productId.'_')) {
                    $inCart += $item['quantity'] ?? 0;
                }
            }

            $canProduceOne = $this->inventoryService->canProduceProduct($productId, 1);
            $canProduceMore = $this->inventoryService->canProduceProduct($productId, $inCart + 1);

            $availability[$productId] = [
                'can_add' => $canProduceOne,
                'can_increment' => $canProduceMore,
                'max_quantity' => $this->getMaxProducibleQuantity($productId),
            ];
        }

        return $availability;
    }

    /**
     * Calculate customization price
     */
    public function calculateCustomizationPrice(array $customizations): float
    {
        $price = 0.0;

        // Add price for milk alternatives
        if (isset($customizations['milk']) && in_array($customizations['milk'], ['oat', 'almond', 'soy'])) {
            $price += 0.50;
        }

        // Add price for extra shots
        if (isset($customizations['extra_shots'])) {
            $price += $customizations['extra_shots'] * 0.75;
        }

        // Add price for syrups
        if (isset($customizations['syrup']) && $customizations['syrup'] !== 'none') {
            $price += 0.60;
        }

        return $price;
    }

    /**
     * Calculate cart totals
     */
    public function calculateCartTotals(array $cart, array $addOns = [], float $discountAmount = 0): array
    {
        $subtotal = 0.0;

        foreach ($cart as $item) {
            $subtotal += ($item['price'] * $item['quantity']);
        }

        // Add add-ons to subtotal
        foreach ($addOns as $addOn) {
            $subtotal += (float) ($addOn['amount'] ?? 0);
        }

        $taxAmount = 0;
        $total = $subtotal + $taxAmount - $discountAmount;

        return [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
        ];
    }

    /**
     * Get low stock alerts
     */
    public function getLowStockAlerts(): array
    {
        return $this->inventoryService->checkLowStock()
            ->map(function ($inventory) {
                return [
                    'ingredient_name' => $inventory->ingredient->name,
                    'current_stock' => $inventory->current_stock,
                    'min_stock_level' => $inventory->min_stock_level,
                    'unit_type' => $inventory->ingredient->unit_type,
                ];
            })
            ->toArray();
    }
}
