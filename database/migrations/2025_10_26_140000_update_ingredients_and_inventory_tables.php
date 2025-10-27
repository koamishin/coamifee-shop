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
        // Add missing columns to ingredient_inventories table
        Schema::table('ingredient_inventories', function (Blueprint $table): void {
            $table->decimal('reorder_level', 10, 3)->default(0)->after('min_stock_level');
            $table->decimal('unit_cost', 8, 2)->nullable()->after('reorder_level');
            $table->text('supplier_info')->nullable()->after('location');
        });

        // Remove columns from ingredients table
        Schema::table('ingredients', function (Blueprint $table): void {
            $table->dropColumn(['description', 'is_trackable', 'current_stock', 'unit_cost', 'supplier']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add columns back to ingredients table
        Schema::table('ingredients', function (Blueprint $table): void {
            $table->text('description')->nullable();
            $table->boolean('is_trackable')->default(true);
            $table->decimal('current_stock', 10, 3)->default(0);
            $table->decimal('unit_cost', 8, 2)->nullable();
            $table->string('supplier')->nullable();
        });

        // Remove columns from ingredient_inventories table
        Schema::table('ingredient_inventories', function (Blueprint $table): void {
            $table->dropColumn(['reorder_level', 'unit_cost', 'supplier_info']);
        });
    }
};
