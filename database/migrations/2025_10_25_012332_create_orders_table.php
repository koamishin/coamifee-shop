<?php

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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('order_type', ['dine-in', 'takeout', 'delivery'])->default('dine-in');
            $table->enum('payment_method', ['cash', 'card', 'gcash', 'paypal'])->default('cash');
            $table->decimal('total', 8, 2);
            $table->enum('status', ['pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled'])->default('pending');
            $table->string('table_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
