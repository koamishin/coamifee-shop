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
        Schema::create('ingredient_inventories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ingredient_id')->constrained()->onDelete('cascade');
            $table->decimal('current_stock', 10, 3);
            $table->decimal('min_stock_level', 10, 3)->default(0);
            $table->decimal('max_stock_level', 10, 3)->nullable();
            $table->string('location')->nullable();
            $table->timestamp('last_restocked_at')->nullable();
            $table->timestamps();

            $table->unique('ingredient_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredient_inventories');
    }
};
