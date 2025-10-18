<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt('password'), // Default password
            ]
        );

        // Create categories
        $categories = Category::factory(6)->create();

        // Create products and their inventory
        Product::factory(20)->create()->each(function ($product) {
            Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity' => rand(10, 100),
                'minimum_stock' => rand(5, 20),
                'unit_cost' => $product->cost,
            ]);
        });

        // Create customers
        Customer::factory(50)->create();

        $this->command->info('Database seeded with coffee shop data!');
    }
}
