<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\PosCheckoutAction;
use App\Models\Order;
use App\Models\Product;
use App\Services\PosService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PosApiController extends Controller
{
    public function __construct(
        private PosService $posService,
        private PosCheckoutAction $posCheckoutAction
    ) {}

    public function getProducts(Request $request): JsonResponse
    {
        $categoryId = $request->input('category_id');
        $search = $request->input('search');

        $products = $this->posService->getFilteredProducts($categoryId, $search);
        $availability = $this->posService->updateProductAvailability($products);

        return response()->json([
            'products' => $products->map(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'image' => $product->image_url,
                'description' => $product->description,
                'category' => [
                    'id' => $product->category->id ?? null,
                    'name' => $product->category->name ?? 'Unknown',
                ],
                'availability' => $availability[$product->id] ?? [
                    'can_produce' => true,
                    'max_quantity' => 999,
                    'stock_status' => 'in_stock',
                ],
            ]),
        ]);
    }

    public function getCategories(): JsonResponse
    {
        $categories = $this->posService->getActiveCategories();

        return response()->json([
            'categories' => $categories->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'icon' => $category->icon,
                'is_active' => $category->is_active,
            ]),
        ]);
    }

    public function addToCart(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::find($request->product_id);
        if (! $product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        if (! $this->posService->canAddToCart($request->product_id)) {
            return response()->json([
                'error' => "Cannot add {$product->name}: Insufficient ingredients",
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "{$product->name} added to cart",
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
            ],
        ]);
    }

    public function calculateTotals(Request $request): JsonResponse
    {
        $request->validate([
            'cart' => 'required|array',
            'add_ons' => 'array',
            'discount_amount' => 'numeric|min:0',
        ]);

        $cart = $request->input('cart', []);
        $addOns = $request->input('add_ons', []);
        $discountAmount = $request->input('discount_amount', 0);

        $totals = $this->posService->calculateCartTotals($cart, $addOns, $discountAmount);

        return response()->json([
            'totals' => $totals,
            'cart_count' => $this->getCartItemCount($cart),
        ]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $request->validate([
            'cart' => 'required|array',
            'customer_name' => 'nullable|string|max:255',
            'order_type' => 'required|in:dine-in,take-out,delivery',
            'table_number' => 'nullable|string|max:50',
            'payment_method' => 'required|in:cash,gcash,maya',
            'add_ons' => 'array',
            'notes' => 'nullable|string|max:500',
            'discount_amount' => 'numeric|min:0',
        ]);

        if (empty($request->cart)) {
            return response()->json(['error' => 'Cart is empty'], 422);
        }

        try {
            $orderData = [
                'customer_name' => $request->customer_name ?: 'Guest',
                'order_type' => $request->order_type,
                'payment_method' => $request->payment_method,
                'table_number' => $request->table_number,
                'total' => $this->posService->calculateCartTotals(
                    $request->cart,
                    $request->add_ons ?? [],
                    $request->discount_amount ?? 0
                )['total'],
                'notes' => $request->notes,
            ];

            $result = $this->posCheckoutAction->execute($request->cart, $orderData);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'order' => [
                        'order_id' => $result['order_id'],
                        'order_number' => $result['order_number'],
                        'total' => $result['total'],
                    ],
                ]);
            }

            return response()->json([
                'error' => $result['message'],
            ], 422);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Checkout failed: '.$e->getMessage(),
            ], 500);
        }
    }

    public function getQuickItems(): JsonResponse
    {
        $quickItems = $this->posService->getQuickAddItems();

        return response()->json([
            'quick_items' => $quickItems,
        ]);
    }

    public function getBestSellers(): JsonResponse
    {
        $bestSellers = $this->posService->getBestSellers();

        return response()->json([
            'best_sellers' => $bestSellers->map(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'image' => $product->image_url,
                'category' => $product->category->name ?? 'Unknown',
            ]),
        ]);
    }

    public function getRecentOrders(): JsonResponse
    {
        $recentOrders = Order::with('items.product')
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'recent_orders' => $recentOrders->map(fn ($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name,
                'total' => $order->total,
                'created_at' => $order->created_at->format('M d, Y H:i'),
                'items_count' => $order->items->count(),
                'payment_method' => $order->payment_method,
            ]),
        ]);
    }

    public function getStats(): JsonResponse
    {
        $todayOrders = Order::query()->whereDate('created_at', today())->count();
        $todaySales = Order::query()->whereDate('created_at', today())->where('payment_status', 'paid')->sum('total');
        $lowStockAlerts = $this->posService->getLowStockAlerts();

        return response()->json([
            'stats' => [
                'today_orders' => $todayOrders,
                'today_sales' => $todaySales,
                'low_stock_count' => count($lowStockAlerts),
            ],
            'low_stock_alerts' => $lowStockAlerts,
        ]);
    }

    private function getCartItemCount(array $cart): int
    {
        return array_sum(array_column($cart, 'quantity'));
    }
}
