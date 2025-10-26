<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Category;
use App\Models\Product;
use App\Services\InventoryService;
use App\Services\ReportingService;
use Livewire\Component;

final class Sidebar extends Component
{
    public string $search = '';

    public int $selectedCategory = 0;

    public array $productAvailability = [];

    protected $listeners = ['refreshInventory' => 'updateProductAvailability'];

    public function boot(
        InventoryService $inventoryService,
        ReportingService $reportingService
    ): void {
        $this->inventoryService = $inventoryService;
        $this->reportingService = $reportingService;
    }

    public function addToCart(int $productId): void
    {
        if (! $this->canAddToCart($productId)) {
            $this->dispatch('insufficient-inventory', [
                'message' => 'Cannot add product: Insufficient ingredients',
                'product_id' => $productId,
            ]);

            return;
        }

        $this->dispatch('productSelected', $productId);
    }

    public function mount(): void
    {
        // Load all products initially
        $products = Product::where('is_active', true)->get();
        $this->updateProductAvailability($products);
    }

    public function render(): \Illuminate\View\View
    {
        // Load categories for sidebar navigation
        $categories = Category::where('is_active', true)->orderBy('name')->get();

        // Load best sellers based on actual metrics data
        $bestSellers = $this->reportingService->getTopProducts(5, 'daily', 7)
            ->map(function ($metric) {
                return $metric->product;
            });

        // Load products for selected category and search
        $products = Product::with(['category', 'ingredients.ingredient'])
            ->where('is_active', true)
            ->when($this->selectedCategory > 0, function ($query) {
                $query->where('category_id', $this->selectedCategory);
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%')
                      ->orWhereHas('category', function ($categoryQuery) {
                          $categoryQuery->where('name', 'like', '%' . $this->search . '%');
                      });
                });
            })
            ->orderBy('name')
            ->get();

        // Update availability for all products
        $this->updateProductAvailability($products);

        return view('livewire.pos-sidebar', compact('categories', 'bestSellers', 'products'));
    }

    private function canAddToCart(int $productId): bool
    {
        return $this->inventoryService->canProduceProduct($productId, 1);
    }

    public function updateProductAvailability($products = null): void
    {
        // If this is called from an event, we need to refresh all products
        if ($products === null) {
            $products = Product::where('is_active', true)->get();
            $this->productAvailability = [];
        }

        foreach ($products as $product) {
            $this->productAvailability[$product->id] = [
                'can_produce' => $this->inventoryService->canProduceProduct($product->id, 1),
                'max_quantity' => $this->getMaxProducibleQuantity($product->id),
                'stock_status' => $this->getStockStatus($product->id),
            ];
        }
    }

    private function getMaxProducibleQuantity(int $productId): int
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
