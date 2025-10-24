<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Livewire\Component;

final class Dashboard extends Component
{
    public function render(): \Illuminate\View\View
    {
        // Get statistics
        $stats = $this->getStatistics();

        // Get recent orders
        $recentOrders = $this->getRecentOrders();

        // Get low stock products
        $lowStockProducts = $this->getLowStockProducts();

        // Get featured products
        $featuredProducts = $this->getFeaturedProducts();

        // Get top selling products
        $topSellingProducts = $this->getTopSellingProducts();

        // Get order status counts
        $orderStatusCounts = $this->getOrderStatusCounts();

        // Get category distribution
        $categoryDistribution = $this->getCategoryDistribution();

        return view('livewire.dashboard', [
            'stats' => $stats,
            'recentOrders' => $recentOrders,
            'lowStockProducts' => $lowStockProducts,
            'featuredProducts' => $featuredProducts,
            'topSellingProducts' => $topSellingProducts,
            'orderStatusCounts' => $orderStatusCounts,
            'categoryDistribution' => $categoryDistribution,
        ]);
    }

    private function getStatistics(): array
    {
        $today = now()->startOfDay();
        $thisWeek = now()->startOfWeek();
        $thisMonth = now()->startOfMonth();

        return [
            'total_products' => Product::count(),
            'active_products' => Product::where('is_active', true)->count(),
            'total_categories' => Category::count(),
            'total_customers' => Customer::count(),
            'total_orders' => Order::count(),
            'today_orders' => Order::whereDate('order_date', $today)->count(),
            'this_week_orders' => Order::where('order_date', '>=', $thisWeek)->count(),
            'this_month_orders' => Order::where('order_date', '>=', $thisMonth)->count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'preparing_orders' => Order::where('status', 'preparing')->count(),
            'ready_orders' => Order::where('status', 'ready')->count(),
            'completed_orders' => Order::where('status', 'completed')->count(),
            'today_revenue' => Order::whereDate('order_date', $today)->sum('total_amount'),
            'this_week_revenue' => Order::where('order_date', '>=', $thisWeek)->sum('total_amount'),
            'this_month_revenue' => Order::where('order_date', '>=', $thisMonth)->sum('total_amount'),
            'low_stock_count' => Product::whereHas('inventory', function ($q) {
                $q->whereRaw('quantity <= minimum_stock');
            })->count(),
            'out_of_stock_count' => Product::whereHas('inventory', function ($q) {
                $q->where('quantity', 0);
            })->count(),
        ];
    }

    private function getRecentOrders(): \Illuminate\Database\Eloquent\Collection
    {
        return Order::with(['customer', 'orderItems.product'])
            ->latest('order_date')
            ->limit(10)
            ->get();
    }

    private function getLowStockProducts(): \Illuminate\Database\Eloquent\Collection
    {
        return Product::with(['category', 'inventory'])
            ->whereHas('inventory', function ($q) {
                $q->whereRaw('quantity <= minimum_stock');
            })
            ->where('is_active', true)
            ->limit(5)
            ->get();
    }

    private function getFeaturedProducts(): \Illuminate\Database\Eloquent\Collection
    {
        return Product::with(['category', 'inventory'])
            ->where('is_featured', true)
            ->where('is_active', true)
            ->limit(6)
            ->get();
    }

    private function getTopSellingProducts(): \Illuminate\Database\Eloquent\Collection
    {
        return Product::with(['category'])
            ->withCount(['orderItems as total_sold' => function ($query) {
                $query->whereHas('order', function ($q) {
                    $q->where('status', 'completed');
                });
            }])
            ->where('is_active', true)
            ->orderBy('total_sold', 'desc')
            ->limit(5)
            ->get();
    }

    private function getOrderStatusCounts(): array
    {
        return [
            'pending' => Order::where('status', 'pending')->count(),
            'confirmed' => Order::where('status', 'confirmed')->count(),
            'preparing' => Order::where('status', 'preparing')->count(),
            'ready' => Order::where('status', 'ready')->count(),
            'completed' => Order::where('status', 'completed')->count(),
            'cancelled' => Order::where('status', 'cancelled')->count(),
        ];
    }

    private function getCategoryDistribution(): \Illuminate\Database\Eloquent\Collection
    {
        return Category::withCount(['products as product_count' => function ($query) {
            $query->where('is_active', true);
        }])
            ->where('is_active', true)
            ->orderBy('product_count', 'desc')
            ->get();
    }
}
