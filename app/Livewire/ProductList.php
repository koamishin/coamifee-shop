<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Category;
use App\Models\Product;
use Exception;
use Livewire\Component;
use Livewire\WithPagination;

final class ProductList extends Component
{
    use WithPagination;

    public string $search = '';

    public int $selectedCategory = 0;

    public string $stockFilter = 'all';

    public string $sortField = 'name';

    public string $sortDirection = 'asc';

    public array $selectedProducts = [];

    public string $bulkAction = '';

    public int $bulkCategoryId = 0;

    public bool $loading = false;

    public int $perPage = 15;

    public bool $loadMore = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'selectedCategory' => ['except' => 0],
        'stockFilter' => ['except' => 'all'],
        'sortField' => ['except' => 'name'],
        'sortDirection' => ['except' => 'asc'],
    ];

    public function mount(): void
    {
        $this->loadMore = false;
    }

    public function render(): \Illuminate\View\View
    {
        try {
            $query = Product::query()->with(['category', 'inventory']);

            // Search
            if ($this->search) {
                $query->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('description', 'like', '%'.$this->search.'%');
            }

            // Category filter
            if ($this->selectedCategory > 0) {
                $query->where('category_id', $this->selectedCategory);
            }

            // Stock filter
            match ($this->stockFilter) {
                'in_stock' => $query->whereHas('inventory', fn ($q) => $q->where('quantity', '>', 0)),
                'low_stock' => $query->whereHas('inventory', fn ($q) => $q->whereRaw('quantity <= minimum_stock')),
                'out_of_stock' => $query->whereHas('inventory', fn ($q) => $q->where('quantity', 0)),
                default => null,
            };

            // Featured filter
            if (request()->get('featured')) {
                $query->where('is_featured', true);
            }

            // Sorting
            $query->orderBy($this->sortField, $this->sortDirection);

            // Only active products
            $query->where('is_active', true);

            $products = $query->paginate($this->perPage);
            $categories = Category::where('is_active', true)->orderBy('name')->get();
            $productCount = $query->count();

            return view('livewire.product-list', [
                'products' => $products,
                'categories' => $categories,
                'productCount' => $productCount,
            ]);
        } catch (Exception $e) {
            // Return empty state if there's an error
            return view('livewire.product-list', [
                'products' => collect(),
                'categories' => collect(),
                'productCount' => 0,
            ]);
        }
    }

    public function updatedSearch(): void
    {
        $this->loading = true;
        $this->resetPage();
        $this->loading = false;
    }

    public function updatedSelectedCategory(): void
    {
        $this->resetPage();
        $this->search = ''; // Reset search when category changes
    }

    public function updatedStockFilter(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function toggleFeatured(int $productId): void
    {
        $product = Product::findOrFail($productId);
        $product->is_featured = ! $product->is_featured;
        $product->save();

        $this->dispatch('product-updated');
    }

    public function toggleActive(int $productId): void
    {
        $product = Product::findOrFail($productId);
        $product->is_active = ! $product->is_active;
        $product->save();

        $this->dispatch('product-updated');
    }

    public function deleteProduct(int $productId): void
    {
        $product = Product::findOrFail($productId);
        $product->delete();

        $this->dispatch('product-deleted');
    }

    public function loadMoreProducts(): void
    {
        $this->loadMore = true;
        $this->perPage += 15;
    }

    public function setPerPage(int $perPage): void
    {
        $this->perPage = $perPage;
        $this->resetPage();
    }

    public function exportProducts(): void
    {
        $products = Product::with(['category', 'inventory'])
            ->when($this->selectedCategory > 0, fn ($q) => $q->where('category_id', $this->selectedCategory))
            ->when($this->search, fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->get();

        $csv = "Name,SKU,Category,Price,Stock,Status\n";

        foreach ($products as $product) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s\n",
                $product->name,
                $product->sku,
                $product->category->name ?? 'N/A',
                $product->formatted_price,
                $product->stock_level,
                $product->inventory?->stock_status ?? 'no_inventory'
            );
        }

        $this->dispatch('download-CSV', ['content' => $csv, 'filename' => 'products_'.now()->format('Y-m-d').'.csv']);
    }

    public function applyBulkAction(): void
    {
        if (empty($this->selectedProducts)) {
            $this->addError('selectedProducts', 'Please select at least one product');

            return;
        }

        match ($this->bulkAction) {
            'delete' => Product::whereIn('id', $this->selectedProducts)->delete(),
            'activate' => Product::whereIn('id', $this->selectedProducts)->update(['is_active' => true]),
            'deactivate' => Product::whereIn('id', $this->selectedProducts)->update(['is_active' => false]),
            'featured' => Product::whereIn('id', $this->selectedProducts)->update(['is_featured' => true]),
            'unfeatured' => Product::whereIn('id', $this->selectedProducts)->update(['is_featured' => false]),
            'update_category' => Product::whereIn('id', $this->selectedProducts)->update(['category_id' => $this->bulkCategoryId]),
            default => null,
        };

        $this->dispatch('bulk-update-completed');
        $this->selectedProducts = [];
        $this->bulkAction = '';
    }

    public function duplicateProduct(int $productId): void
    {
        $originalProduct = Product::findOrFail($productId);

        $newProduct = $originalProduct->replicate([
            'sku',
            'slug',
        ]);

        $newProduct->name = $originalProduct->name.' (Copy)';
        $newProduct->sku = $originalProduct->sku.'-COPY';
        $newProduct->slug = \Illuminate\Support\Str::slug($newProduct->name);
        $newProduct->is_featured = false;
        $newProduct->save();

        $this->dispatch('product-duplicated');
    }

    public function selectAll(): void
    {
        $products = Product::when($this->search, fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->when($this->selectedCategory > 0, fn ($q) => $q->where('category_id', $this->selectedCategory))
            ->pluck('id')
            ->toArray();

        $this->selectedProducts = $products;
    }

    public function clearSelection(): void
    {
        $this->selectedProducts = [];
    }
}
