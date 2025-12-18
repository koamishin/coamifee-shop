<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::table('orders')->whereNull('payment_method')->update(['payment_method' => 'cash']);

            DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_payment_method_check');
            DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_payment_method_check CHECK (payment_method in ('cash', 'gcash', 'maya', 'bank_transfer', 'grab', 'food_panda'))");
            DB::statement("ALTER TABLE orders ALTER COLUMN payment_method SET DEFAULT 'cash'");
            DB::statement('ALTER TABLE orders ALTER COLUMN payment_method SET NOT NULL');

            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->enum('payment_method', ['cash', 'gcash', 'maya', 'bank_transfer', 'grab', 'food_panda'])->default('cash')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::table('orders')
                ->whereIn('payment_method', ['bank_transfer', 'grab', 'food_panda'])
                ->update(['payment_method' => 'cash']);

            DB::table('orders')->whereNull('payment_method')->update(['payment_method' => 'cash']);

            DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_payment_method_check');
            DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_payment_method_check CHECK (payment_method in ('cash', 'gcash', 'maya'))");
            DB::statement("ALTER TABLE orders ALTER COLUMN payment_method SET DEFAULT 'cash'");
            DB::statement('ALTER TABLE orders ALTER COLUMN payment_method SET NOT NULL');

            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->enum('payment_method', ['cash', 'gcash', 'maya'])->default('cash')->change();
        });
    }
};
