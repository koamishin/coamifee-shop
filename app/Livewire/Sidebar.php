<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Category;
use App\Models\Product;
use Livewire\Component;

final class Sidebar extends Component
{
public int $selectedCategory = 0;

    public function addToCart(int $productId): void
    {
        $this->dispatch('productSelected', $productId);
    }

    public function mount(): void
    {
        // Initialize sidebar state
    }

    public function render(): \Illuminate\View\View
    {
    // Load categories for sidebar navigation
    $categories = Category::where('is_active', true)->orderBy('name')->get();

    // Load best sellers (recently added products - in real app, this would be based on sales data)
        $bestSellers = Product::with(['category'])
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('livewire.pos-sidebar', compact('categories', 'bestSellers'));
    }
}
