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
            // Add refunded status to payment_status enum
            $table->enum('payment_status', ['paid', 'unpaid', 'partially_paid', 'refunded'])
                ->default('unpaid')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Revert back to previous status options
            $table->enum('payment_status', ['paid', 'unpaid', 'partially_paid'])
                ->default('unpaid')
                ->change();
        });
    }
};
