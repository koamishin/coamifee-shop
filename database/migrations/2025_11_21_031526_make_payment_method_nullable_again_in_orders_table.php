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

            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->enum('payment_method', ['cash', 'gcash', 'maya'])->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::table('orders')->whereNull('payment_method')->update(['payment_method' => 'cash']);

            DB::statement("ALTER TABLE orders ALTER COLUMN payment_method SET DEFAULT 'cash'");
            DB::statement('ALTER TABLE orders ALTER COLUMN payment_method SET NOT NULL');

            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->enum('payment_method', ['cash', 'gcash', 'maya'])->nullable(false)->default('cash')->change();
        });
    }
};
