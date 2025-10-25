<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::firstOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
            'password' => Hash::make('password'),
        ]);

        // Create sample data for the cafe
        $categories = [
            ['name' => 'Coffee', 'description' => 'Hot and cold coffee beverages'],
            ['name' => 'Tea', 'description' => 'Various types of tea'],
            ['name' => 'Pastries', 'description' => 'Fresh baked pastries and breads'],
            ['name' => 'Sandwiches', 'description' => 'Delicious sandwiches and wraps'],
            ['name' => 'Salads', 'description' => 'Fresh and healthy salads'],
            ['name' => 'Desserts', 'description' => 'Sweet treats and desserts'],
            ['name' => 'Beverages', 'description' => 'Soft drinks and other beverages'],
            ['name' => 'Breakfast', 'description' => 'Breakfast items and specials'],
        ];

        foreach ($categories as $category) {
            \App\Models\Category::create($category);
        }

        \App\Models\Product::factory(20)->create();

        \App\Models\Customer::factory(10)->create();

        \App\Models\Order::factory(15)->create()->each(function ($order) {
            $products = \App\Models\Product::inRandomOrder()->take(rand(1, 5))->get();
            foreach ($products as $product) {
                \App\Models\OrderItem::factory()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'price' => $product->price,
                ]);
            }
        });
    }
}
