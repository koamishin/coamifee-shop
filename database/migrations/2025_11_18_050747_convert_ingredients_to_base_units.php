<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Convert all 'kilograms' to 'grams' in ingredients table
        DB::table('ingredients')
            ->where('unit_type', 'kilograms')
            ->update(['unit_type' => 'grams']);

        // Convert all 'liters' to 'ml' in ingredients table
        DB::table('ingredients')
            ->where('unit_type', 'liters')
            ->update(['unit_type' => 'ml']);

        // Log the conversion
        Illuminate\Support\Facades\Log::info('Converted ingredient unit types to base units', [
            'grams_converted' => DB::table('ingredients')->where('unit_type', 'grams')->count(),
            'ml_converted' => DB::table('ingredients')->where('unit_type', 'ml')->count(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: We cannot reliably reverse this migration
        // because we don't know which grams were originally kilograms
        // and which ml were originally liters
    }
};
