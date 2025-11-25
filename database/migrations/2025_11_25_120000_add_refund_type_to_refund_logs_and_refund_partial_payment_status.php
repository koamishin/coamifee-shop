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
        // Add refund_type column to refund_logs table
        Schema::table('refund_logs', function (Blueprint $table) {
            $table->enum('refund_type', ['full', 'partial'])->default('full')->after('refund_amount');
        });

        // Add refund_partial status to payment_status enum
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('payment_status', ['paid', 'unpaid', 'partially_paid', 'refunded', 'refund_partial', 'cancelled'])
                ->default('unpaid')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('refund_logs', function (Blueprint $table) {
            $table->dropColumn('refund_type');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->enum('payment_status', ['paid', 'unpaid', 'partially_paid', 'refunded', 'refund_partial'])
                ->default('unpaid')
                ->change();
        });
    }
};
