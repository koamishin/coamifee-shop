<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function index(): View
    {
        // Load POS system data
        $products = Product::with(['category', 'inventory'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $categories = Category::query()->where('is_active', true)->orderBy('name')->get();

        $recentOrders = Order::with('items.product')
            ->latest()
            ->take(5)
            ->get();

        $todayOrders = Order::query()->whereDate('created_at', today())->count();
        $todaySales = Order::query()->whereDate('created_at', today())->sum('total');

        // POS system variables (initialize empty)
        $cart = [];
        $search = '';
        $selectedCategory = 0;
        $customerName = '';
        $orderType = 'dine-in';
        $paymentMethod = 'cash';
        $tableNumber = '';
        $subtotal = 0.0;
        $taxRate = 0.0;
        $taxAmount = 0.0;
        $total = 0.0;
        $otherLabel = '';
        $otherAmount = 0.0;
        $otherNote = '';
        $couponCode = '';
        $discountApplied = false;
        $discountAmount = 0.0;
        $customerSearch = '';
        $showFavoritesOnly = false;
        $showPaymentModal = false;
        $showReceiptModal = false;
        $showPaymentPanel = false;
        $selectedProductId = null;
        $selectedProductIds = [];
        $favorites = [];
        $customers = collect();

        return view('dashboard', ['products' => $products, 'categories' => $categories, 'recentOrders' => $recentOrders, 'todayOrders' => $todayOrders, 'todaySales' => $todaySales, 'cart' => $cart, 'search' => $search, 'selectedCategory' => $selectedCategory, 'customerName' => $customerName, 'orderType' => $orderType, 'paymentMethod' => $paymentMethod, 'tableNumber' => $tableNumber, 'subtotal' => $subtotal, 'taxRate' => $taxRate, 'taxAmount' => $taxAmount, 'total' => $total, 'otherLabel' => $otherLabel, 'otherAmount' => $otherAmount, 'otherNote' => $otherNote, 'couponCode' => $couponCode, 'discountApplied' => $discountApplied, 'discountAmount' => $discountAmount, 'customerSearch' => $customerSearch, 'showFavoritesOnly' => $showFavoritesOnly, 'showPaymentModal' => $showPaymentModal, 'showReceiptModal' => $showReceiptModal, 'showPaymentPanel' => $showPaymentPanel, 'selectedProductId' => $selectedProductId, 'selectedProductIds' => $selectedProductIds, 'favorites' => $favorites, 'customers' => $customers]);
    }
}
