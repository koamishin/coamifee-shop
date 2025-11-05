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
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('discount_type')->nullable()->after('total'); // 'percentage' or 'fixed'
            $table->decimal('discount_value', 8, 2)->nullable()->after('discount_type'); // Value or percentage
            $table->decimal('discount_amount', 8, 2)->default(0)->after('discount_value'); // Calculated discount
            $table->decimal('subtotal', 8, 2)->nullable()->after('discount_amount'); // Total before discount
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn(['discount_type', 'discount_value', 'discount_amount', 'subtotal']);
        });
    }
};
