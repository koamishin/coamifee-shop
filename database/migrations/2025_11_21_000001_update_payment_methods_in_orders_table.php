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
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_payment_method_check');
            DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_payment_method_check CHECK (payment_method in ('cash', 'card', 'gcash', 'paypal', 'maya'))");

            DB::table('orders')->where('payment_method', 'card')->update(['payment_method' => 'gcash']);
            DB::table('orders')->where('payment_method', 'paypal')->update(['payment_method' => 'maya']);
            DB::table('orders')->whereNull('payment_method')->update(['payment_method' => 'cash']);

            DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_payment_method_check');
            DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_payment_method_check CHECK (payment_method in ('cash', 'gcash', 'maya'))");
            DB::statement("ALTER TABLE orders ALTER COLUMN payment_method SET DEFAULT 'cash'");
            DB::statement('ALTER TABLE orders ALTER COLUMN payment_method SET NOT NULL');

            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->enum('payment_method', ['cash', 'card', 'gcash', 'paypal', 'maya'])->nullable()->default('cash')->change();
        });

        DB::table('orders')->where('payment_method', 'card')->update(['payment_method' => 'gcash']);
        DB::table('orders')->where('payment_method', 'paypal')->update(['payment_method' => 'maya']);
        DB::table('orders')->whereNull('payment_method')->update(['payment_method' => 'cash']);

        Schema::table('orders', function (Blueprint $table): void {
            $table->enum('payment_method', ['cash', 'gcash', 'maya'])->default('cash')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_payment_method_check');
            DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_payment_method_check CHECK (payment_method in ('cash', 'card', 'gcash', 'paypal', 'maya'))");

            DB::table('orders')->where('payment_method', 'maya')->update(['payment_method' => 'paypal']);

            DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_payment_method_check');
            DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_payment_method_check CHECK (payment_method in ('cash', 'card', 'gcash', 'paypal'))");
            DB::statement("ALTER TABLE orders ALTER COLUMN payment_method SET DEFAULT 'cash'");

            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->enum('payment_method', ['cash', 'card', 'gcash', 'paypal', 'maya'])->nullable()->default('cash')->change();
        });

        DB::table('orders')->where('payment_method', 'maya')->update(['payment_method' => 'paypal']);

        Schema::table('orders', function (Blueprint $table): void {
            $table->enum('payment_method', ['cash', 'card', 'gcash', 'paypal'])->default('cash')->change();
        });
    }
};
