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

        $user = User::query()->firstOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
            'password' => Hash::make('password'),
        ]);

        // $user->assignRole('super_admin');

        // Use the CoffeeShopSeeder for existing data
        $this->call(CoffeeShopSeeder::class);

        // Seed Goodland Kitchen and Bar inventory
        $this->call(GoodlandInventorySeeder::class);

        // Seed test data for enhanced UI testing
        $this->call(TestProductIngredientsSeeder::class);
    }
}
