<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Make payment_method nullable again for restaurant-style workflow
            // Payment is collected after order is ready, not when order is created
            $table->enum('payment_method', ['cash', 'gcash', 'maya'])->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Revert back to non-nullable with default
            $table->enum('payment_method', ['cash', 'gcash', 'maya'])->nullable(false)->default('cash')->change();
        });
    }
};
