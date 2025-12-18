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
        // Add refund_type column to refund_logs table
        Schema::table('refund_logs', function (Blueprint $table): void {
            $table->enum('refund_type', ['full', 'partial'])->default('full')->after('refund_amount');
        });

        // Add refund_partial status to payment_status enum
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::table('orders')->whereNull('payment_status')->update(['payment_status' => 'unpaid']);

            DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_payment_status_check');
            DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_payment_status_check CHECK (payment_status in ('paid', 'unpaid', 'partially_paid', 'refunded', 'refund_partial', 'cancelled'))");
            DB::statement("ALTER TABLE orders ALTER COLUMN payment_status SET DEFAULT 'unpaid'");
            DB::statement('ALTER TABLE orders ALTER COLUMN payment_status SET NOT NULL');
        } else {
            Schema::table('orders', function (Blueprint $table): void {
                $table->enum('payment_status', ['paid', 'unpaid', 'partially_paid', 'refunded', 'refund_partial', 'cancelled'])
                    ->default('unpaid')
                    ->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('refund_logs', function (Blueprint $table): void {
            $table->dropColumn('refund_type');
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::table('orders')->where('payment_status', 'cancelled')->update(['payment_status' => 'unpaid']);
            DB::table('orders')->whereNull('payment_status')->update(['payment_status' => 'unpaid']);

            DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_payment_status_check');
            DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_payment_status_check CHECK (payment_status in ('paid', 'unpaid', 'partially_paid', 'refunded', 'refund_partial'))");
            DB::statement("ALTER TABLE orders ALTER COLUMN payment_status SET DEFAULT 'unpaid'");
            DB::statement('ALTER TABLE orders ALTER COLUMN payment_status SET NOT NULL');
        } else {
            Schema::table('orders', function (Blueprint $table): void {
                $table->enum('payment_status', ['paid', 'unpaid', 'partially_paid', 'refunded', 'refund_partial'])
                    ->default('unpaid')
                    ->change();
            });
        }
    }
};
