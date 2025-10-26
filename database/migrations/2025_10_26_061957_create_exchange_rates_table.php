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
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('base_currency', 3);
            $table->string('target_currency', 3);
            $table->decimal('rate', 18, 8);
            $table->json('rates_data')->nullable(); // Store all rates as backup
            $table->timestamp('fetched_at');
            $table->timestamp('expires_at');
            $table->string('source', 50)->default('api'); // api, cache, manual
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(
                ['base_currency', 'target_currency'],
                'unique_currency_pair',
            );
            $table->index(['base_currency', 'expires_at'], 'idx_base_expires');
            $table->index(['fetched_at'], 'idx_fetched_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
