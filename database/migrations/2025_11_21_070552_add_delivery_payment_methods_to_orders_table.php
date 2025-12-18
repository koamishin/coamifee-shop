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
            DB::statement('ALTER TABLE orders ALTER COLUMN payment_method DROP NOT NULL');
            DB::statement('ALTER TABLE orders ALTER COLUMN payment_method DROP DEFAULT');

            DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_payment_method_check');
            DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_payment_method_check CHECK (payment_method in ('cash', 'gcash', 'maya', 'bank_transfer', 'grab', 'food_panda'))");

            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->enum('payment_method', ['cash', 'gcash', 'maya', 'bank_transfer', 'grab', 'food_panda'])->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::table('orders')->whereIn('payment_method', ['grab', 'food_panda'])->update(['payment_method' => null]);

            DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_payment_method_check');
            DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_payment_method_check CHECK (payment_method in ('cash', 'gcash', 'maya', 'bank_transfer'))");

            DB::statement('ALTER TABLE orders ALTER COLUMN payment_method DROP NOT NULL');
            DB::statement('ALTER TABLE orders ALTER COLUMN payment_method DROP DEFAULT');

            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->enum('payment_method', ['cash', 'gcash', 'maya', 'bank_transfer'])->nullable()->change();
        });
    }
};
