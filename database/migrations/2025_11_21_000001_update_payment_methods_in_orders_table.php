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
            // Update the enum to replace 'card' with 'gcash' and 'paypal' with 'maya'
            $table->enum('payment_method', ['cash', 'gcash', 'maya'])->default('cash')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Revert back to original payment methods
            $table->enum('payment_method', ['cash', 'card', 'gcash', 'paypal'])->default('cash')->change();
        });
    }
};
