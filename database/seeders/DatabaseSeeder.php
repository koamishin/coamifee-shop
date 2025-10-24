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
        // Always create admin user first
        $this->command->info('Creating admin user...');
        User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        $this->command->info('✅ Admin user created: admin@admin.com / password');

        // Create categories
        $this->command->info('Creating categories...');
        $categories = Category::factory(6)->create();
        $this->command->info('✅ Created '.$categories->count().' categories');

        // Create products with unique SKUs to avoid conflicts
        $this->command->info('Creating products...');
        for ($i = 1; $i <= 20; $i++) {
            $product = Product::factory()->create([
                'sku' => 'PROD-'.mb_str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'category_id' => $categories->random()->id,
            ]);

            Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity' => rand(10, 100),
                'minimum_stock' => rand(5, 20),
                'unit_cost' => $product->cost,
            ]);
        }
        $this->command->info('✅ Created 20 products with inventory');

        // Create customers
        $this->command->info('Creating customers...');
        Customer::factory(50)->create();
        $this->command->info('✅ Created 50 customers');

        $this->command->info('');
        $this->command->info('🎉 Database seeded successfully!');
        $this->command->info('📧 Admin Login: admin@admin.com');
        $this->command->info('🔑 Password: password');
    }
}
