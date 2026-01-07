<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

final class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Create roles if they don't exist
        Role::query()->firstOrCreate(['name' => 'super_admin'], ['guard_name' => 'web']);
        Role::query()->firstOrCreate(['name' => 'cashier'], ['guard_name' => 'web']);

        $user = User::query()->firstOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
            'password' => Hash::make('password'),
        ]);

        // Assign both roles to super admin user
        $user->syncRoles(['super_admin', 'cashier']);

        // Use the CoffeeShopSeeder for existing data
        $this->call(CoffeeShopSeeder::class);

        // Seed Goodland Kitchen and Bar inventory
        $this->call(GoodlandInventorySeeder::class);

        // Seed test data for enhanced UI testing
        $this->call(TestProductIngredientsSeeder::class);
    }
}
