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
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::table('orders')->whereNull('status')->update(['status' => 'pending']);

            DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_status_check');
            DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_status_check CHECK (status in ('pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled', 'refunded'))");
            DB::statement("ALTER TABLE orders ALTER COLUMN status SET DEFAULT 'pending'");
            DB::statement('ALTER TABLE orders ALTER COLUMN status SET NOT NULL');

            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->enum('status', ['pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled', 'refunded'])
                ->default('pending')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::table('orders')->where('status', 'refunded')->update(['status' => 'completed']);
            DB::table('orders')->whereNull('status')->update(['status' => 'pending']);

            DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_status_check');
            DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_status_check CHECK (status in ('pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled'))");
            DB::statement("ALTER TABLE orders ALTER COLUMN status SET DEFAULT 'pending'");
            DB::statement('ALTER TABLE orders ALTER COLUMN status SET NOT NULL');

            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->enum('status', ['pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled'])
                ->default('pending')
                ->change();
        });
    }
};
