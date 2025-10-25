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
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained()->onDelete('cascade');
            $table->enum('transaction_type', ['restock', 'usage', 'adjustment', 'waste']);
            $table->decimal('quantity_change', 10, 3);
            $table->decimal('previous_stock', 10, 3);
            $table->decimal('new_stock', 10, 3);
            $table->text('reason')->nullable();
            $table->foreignId('order_item_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
