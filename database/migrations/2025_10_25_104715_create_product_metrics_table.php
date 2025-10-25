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
        Schema::create('product_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->date('metric_date');
            $table->integer('orders_count')->default(0);
            $table->decimal('total_revenue', 10, 2)->default(0);
            $table->enum('period_type', ['daily', 'weekly', 'monthly']);
            $table->timestamps();

            $table->unique(['product_id', 'metric_date', 'period_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_metrics');
    }
};
