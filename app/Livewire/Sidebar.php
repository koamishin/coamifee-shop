<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\InventoryService;
use App\Services\PosService;
use App\Services\ReportingService;
use Illuminate\View\View;
use Livewire\Component;

final class Sidebar extends Component
{
    public string $search = '';

    public int $selectedCategory = 0;

    public array $productAvailability = [];

    protected $listeners = ['refreshInventory' => 'updateProductAvailability'];
    private InventoryService $inventoryService;
    private ReportingService $reportingService;
    private PosService $posService;

    public function selectCategory(int $categoryId): void
    {
        $this->selectedCategory = $categoryId;
        // Dispatch event to notify Pos component
        $this->dispatch('categorySelected', $categoryId);
    }

    public function boot(
        InventoryService $inventoryService,
        ReportingService $reportingService,
        PosService $posService
    ): void {
        $this->inventoryService = $inventoryService;
        $this->reportingService = $reportingService;
        $this->posService = $posService;
    }

    public function addToCart(int $productId): void
    {
        if (! $this->posService->canAddToCart($productId)) {
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
        $products = $this->posService->getFilteredProducts();
        $this->updateProductAvailability($products);
    }

    public function render(): View
    {
        // Load categories for sidebar navigation
        $categories = $this->posService->getActiveCategories();

        // Load best sellers based on actual metrics data
        $bestSellers = $this->posService->getBestSellers();

        // Load products for selected category and search
        $products = $this->posService->getFilteredProducts($this->selectedCategory, $this->search);

        // Update availability for all products
        $this->updateProductAvailability($products);

        return view('livewire.pos-sidebar', ['categories' => $categories, 'bestSellers' => $bestSellers, 'products' => $products]);
    }

    public function updateProductAvailability($products = null): void
    {
        // If this is called from an event, we need to refresh all products
        if ($products === null) {
            $products = $this->posService->getFilteredProducts();
            $this->productAvailability = [];
        }

        $this->productAvailability = $this->posService->updateProductAvailability($products);
    }
}
