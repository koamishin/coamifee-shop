<?php

declare(strict_types=1);

namespace App\Services\Pos;

use App\Models\Category;
use App\Models\Product;
use App\Services\InventoryService;
use App\Services\ReportingService;
use Illuminate\Support\Collection;

final readonly class PosProductService
{
    public function __construct(
        private InventoryService $inventoryService,
        private ReportingService $reportingService
    ) {}

    public function getCategories(): Collection
    {
        return Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function getBestSellers(int $limit = 5): Collection
    {
        return $this->reportingService
            ->getTopProducts($limit, 'daily', 7)
            ->map(fn ($metric) => $metric->product);
    }

    public function getFilteredProducts(?int $categoryId = null, ?string $search = null): Collection
    {
        return Product::with(['category', 'ingredients.ingredient'])
            ->where('is_active', true)
            ->when($categoryId && $categoryId > 0, function ($query) use ($categoryId): void {
                $query->where('category_id', $categoryId);
            })
            ->when($search, function ($query) use ($search): void {
                $query->where(function ($q) use ($search): void {
                    $q->where('name', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%')
                        ->orWhereHas('category', function ($categoryQuery) use ($search): void {
                            $categoryQuery->where('name', 'like', '%'.$search.'%');
                        });
                });
            })
            ->orderBy('name')
            ->get();
    }

    public function getProductAvailability(Collection $products): array
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

    public function canAddToCart(int $productId): bool
    {
        return $this->inventoryService->canProduceProduct($productId, 1);
    }

    public function getMaxProducibleQuantity(int $productId): int
    {
        $product = Product::query()->find($productId);
        if (! $product) {
            return 0;
        }

        $ingredients = $product->ingredients()->with('ingredient.inventory')->get();
        $maxQuantities = [];

        /** @var \App\Models\ProductIngredient $productIngredient */
        foreach ($ingredients as $productIngredient) {
            /** @var \App\Models\Ingredient $ingredient */
            $ingredient = $productIngredient->ingredient;
            /** @var \App\Models\IngredientInventory|null $inventory */
            $inventory = $ingredient->inventory;
            if ($inventory) {
                $maxQuantities[] = (int) ($inventory->current_stock / $productIngredient->quantity_required);
            } else {
                return 0;
            }
        }

        return $maxQuantities === [] ? 999 : min($maxQuantities);
    }

    private function getStockStatus(int $productId): string
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
}
