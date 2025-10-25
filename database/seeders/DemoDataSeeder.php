<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\IngredientUsage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductIngredient;
use App\Models\ProductMetric;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

final class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->createDemoOrders();
        $this->createProductMetrics();
        $this->createIngredientUsage();
    }

    private function createDemoOrders(): void
    {
        // Create some demo customers
        $customers = Customer::factory(10)->create();

        // Get products with recipes
        $productsWithIngredients = ProductIngredient::pluck('product_id')->unique()->toArray();

        // Create 50 demo orders over the last 30 days
        for ($i = 0; $i < 50; $i++) {
            $orderDate = Carbon::now()->subDays(rand(1, 30));

            $order = Order::create([
                'customer_name' => $customers->random()->name,
                'customer_id' => $customers->random()->id,
                'order_type' => ['dine-in', 'takeout', 'delivery'][rand(0, 2)],
                'payment_method' => ['cash', 'card', 'gcash'][rand(0, 2)],
                'total' => 0,
                'status' => 'completed',
                'notes' => rand(0, 1) === 1 ? 'Extra napkins requested' : null,
                'created_at' => $orderDate,
                'updated_at' => $orderDate,
            ]);

            // Add 2-4 items per order
            $numItems = rand(2, 4);
            $orderTotal = 0;

            for ($j = 0; $j < $numItems; $j++) {
                $product = $productsWithIngredients[array_rand($productsWithIngredients)];

                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product,
                    'quantity' => rand(1, 3),
                    'price' => 0,
                    'notes' => rand(0, 1) === 1 ? 'No sugar' : null,
                ]);

                // Set actual product price
                $productModel = \App\Models\Product::find($product);
                $orderItem->price = $productModel->price;
                $orderItem->save();

                $orderTotal += $orderItem->price * $orderItem->quantity;
            }

            $order->total = $orderTotal;
            $order->save();
        }
    }

    private function createProductMetrics(): void
    {
        // Create metrics for the last 7 days
        $products = \App\Models\Product::all();

        for ($i = 0; $i < 7; $i++) {
            $date = Carbon::now()->subDays($i);

            foreach ($products as $product) {
                // Calculate orders for this product on this date
                $orders = Order::whereDate('created_at', $date)
                    ->whereHas('items', function ($query) use ($product) {
                        $query->where('product_id', $product->id);
                    })
                    ->get();

                $totalOrders = 0;
                $totalRevenue = 0;

                foreach ($orders as $order) {
                    $orderItems = $order->items()->where('product_id', $product->id)->get();

                    foreach ($orderItems as $item) {
                        $totalOrders += $item->quantity;
                        $totalRevenue += $item->price * $item->quantity;
                    }
                }

                if ($totalOrders > 0) {
                    ProductMetric::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'metric_date' => $date->toDateString(),
                            'period_type' => 'daily',
                        ],
                        [
                            'orders_count' => $totalOrders,
                            'total_revenue' => $totalRevenue,
                        ]
                    );
                }
            }
        }
    }

    private function createIngredientUsage(): void
    {
        $orderItems = OrderItem::with(['product.ingredients', 'order'])->get();

        foreach ($orderItems as $orderItem) {
            $productIngredients = $orderItem->product->ingredients;

            foreach ($productIngredients as $productIngredient) {
                $ingredient = $productIngredient->ingredient;
                $quantityNeeded = $productIngredient->quantity_required * $orderItem->quantity;

                // Record usage for both trackable and untrackable ingredients
                IngredientUsage::updateOrCreate(
                    [
                        'order_item_id' => $orderItem->id,
                        'ingredient_id' => $ingredient->id,
                    ],
                    [
                        'quantity_used' => $quantityNeeded,
                        'recorded_at' => $orderItem->order->created_at,
                    ]
                );
            }
        }
    }
}
