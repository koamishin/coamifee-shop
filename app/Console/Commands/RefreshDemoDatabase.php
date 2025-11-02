<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class RefreshDemoDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refresh:demo-database';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh the demo database with fresh seed data';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Only run in demo environment
        if (config('app.env') !== 'demo') {
            $this->error('This command can only be run in demo environment!');

            return self::FAILURE;
        }

        $this->info('Refreshing demo database...');

        // Drop all tables
        DB::statement('PRAGMA writable_schema = main;');
        DB::statement('PRAGMA foreign_keys = OFF;');
        $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        foreach ($tables as $table) {
            DB::statement("DROP TABLE IF EXISTS {$table->name}");
        }
        DB::statement('PRAGMA foreign_keys = ON;');

        // Run migrations
        $this->call('migrate:fresh', ['--force' => true]);

        // Generate shield permissions
        $this->call('shield:generate', ['--all' => true, '--panel' => 'admin', '--no-interaction' => true]);

        // Seed the database
        $this->call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);

        // Create super admin for shield
        $this->call('shield:super-admin', ['--no-interaction' => true, '--panel' => 'admin']);

        $this->info('Demo database refreshed successfully!');

        return self::SUCCESS;
    }
}
