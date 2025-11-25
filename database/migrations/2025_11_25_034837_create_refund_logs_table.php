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
        Schema::create('refund_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('refunded_by')->constrained('users')->onDelete('cascade');
            $table->decimal('refund_amount', 10, 2);
            $table->string('payment_method');
            $table->timestamp('refund_date');
            $table->timestamps();

            $table->index(['order_id', 'refund_date']);
            $table->index(['payment_method', 'refund_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refund_logs');
    }
};
