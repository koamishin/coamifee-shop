<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Currency;
use App\Services\CurrencyConverter;
use Exception;
use Illuminate\Console\Command;
use ValueError;

final class ExchangeRateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exchange:rates {action? : Action to perform (refresh, stats, export, import, test, cleanup)}
                                     {--base=USD : Base currency to work with}
                                     {--file= : File path for import/export}
                                     {--force-offline : Force offline mode for testing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage exchange rates - refresh, export, import, and test offline capabilities';

    /**
     * Execute the console command.
     */
    public function handle(CurrencyConverter $converter): int
    {
        $action = $this->argument('action') ?? 'status';
        $baseCurrencyCode = $this->option('base') ?? 'USD';

        try {
            $baseCurrency = Currency::from($baseCurrencyCode);
        } catch (ValueError) {
            $this->error("Invalid base currency: {$baseCurrencyCode}");

            return 1;
        }

        if ($this->option('force-offline')) {
            $converter->forceOffline(true);
            $this->warn('🔌 Operating in offline mode');
        }

        return match ($action) {
            'refresh' => $this->refreshRates($converter, $baseCurrency),
            'status', 'stats' => $this->showStats($converter, $baseCurrency),
            'export' => $this->exportRates($converter, $baseCurrency),
            'import' => $this->importRates($converter, $baseCurrency),
            'test' => $this->testOfflineMode($converter, $baseCurrency),
            'cleanup' => $this->cleanupExpiredRates($converter),
            default => $this->showHelp(),
        };
    }

    /**
     * Refresh exchange rates from API
     */
    private function refreshRates(
        CurrencyConverter $converter,
        Currency $base,
    ): int {
        $this->info("🔄 Refreshing exchange rates for {$base->value}...");

        // Don't check offline mode for refresh - we want to force API call
        if ($this->option('force-offline')) {
            $this->warn('⚠️  Cannot refresh in force offline mode');

            return 1;
        }

        $this->withProgressBar(1, function () use ($converter, $base): void {
            $success = $converter->refreshRates($base);
            throw_unless($success, Exception::class, 'Failed to refresh rates');
        });

        $this->newLine();
        $this->info(
            "✅ Successfully refreshed exchange rates for {$base->value}",
        );

        return 0;
    }

    /**
     * Show statistics about stored rates
     */
    private function showStats(
        CurrencyConverter $converter,
        Currency $base,
    ): int {
        $this->info("📊 Exchange Rates Statistics for {$base->value}");
        $this->info(str_repeat('=', 50));

        $stats = $converter->getStoredRatesStats($base);

        $this->table(
            ['Metric', 'Value'],
            [
                [
                    'Has Fresh Rates',
                    $stats['has_fresh_rates'] ? '✅ Yes' : '❌ No',
                ],
                ['Total Rates', $stats['total_rates']],
                ['Last Updated', $stats['last_updated'] ?? 'Never'],
                [
                    'Offline Capable',
                    $stats['is_offline_capable'] ? '✅ Yes' : '❌ No',
                ],
                [
                    'Current Mode',
                    $converter->isOffline() ? '🔌 Offline' : '🌐 Online',
                ],
            ],
        );

        if (! $stats['is_offline_capable']) {
            $this->warn(
                '⚠️  No offline rates available. Run "php artisan exchange:rates refresh" first.',
            );
        }

        return 0;
    }

    /**
     * Export rates to file for backup
     */
    private function exportRates(
        CurrencyConverter $converter,
        Currency $base,
    ): int {
        $filePath =
            $this->option('file') ??
            storage_path("app/exchange_rates_{$base->value}_backup.json");

        $this->info(
            "📤 Exporting exchange rates for {$base->value} to {$filePath}",
        );

        $exportData = $converter->exportRatesForBackup($base);
        $jsonContent = json_encode($exportData, JSON_PRETTY_PRINT);

        if (file_put_contents($filePath, $jsonContent)) {
            $this->info(
                '✅ Successfully exported '.
                    count($exportData['rates']).
                    ' rates',
            );

            return 0;
        }

        $this->error('❌ Failed to export rates');

        return 1;
    }

    /**
     * Import rates from backup file
     */
    private function importRates(
        CurrencyConverter $converter,
        Currency $base,
    ): int {
        $filePath = $this->option('file');

        if (! $filePath) {
            $this->error('❌ --file option is required for import');

            return 1;
        }

        if (! file_exists($filePath)) {
            $this->error("❌ File not found: {$filePath}");

            return 1;
        }

        $this->info(
            "📥 Importing exchange rates for {$base->value} from {$filePath}",
        );

        $content = file_get_contents($filePath);
        $data = json_decode($content, true);

        if (! $data || ! isset($data['rates'])) {
            $this->error('❌ Invalid backup file format');

            return 1;
        }

        $success = $converter->importBackupRates($base, $data['rates']);

        if ($success) {
            $this->info(
                '✅ Successfully imported '.count($data['rates']).' rates',
            );

            return 0;
        }

        $this->error('❌ Failed to import rates');

        return 1;
    }

    /**
     * Test offline mode capabilities
     */
    private function testOfflineMode(
        CurrencyConverter $converter,
        Currency $base,
    ): int {
        $this->info("🧪 Testing offline mode capabilities for {$base->value}");
        $this->info(str_repeat('=', 50));

        // First, ensure we have some rates
        if (! $converter->areRatesAvailable($base)) {
            $this->info('📥 No rates available, fetching first...');
            try {
                $converter->getExchangeRates($base);
            } catch (Exception) {
                $this->warn('⚠️  Could not fetch rates, using fallback');
            }
        }

        // Test online mode
        $this->info('🌐 Testing online mode...');
        try {
            $onlineRate = $converter->getExchangeRate($base, Currency::PHP);
            $this->info(
                "✅ Online rate ({$base->value} to PHP): {$onlineRate}",
            );
        } catch (Exception $e) {
            $this->warn('⚠️  Online mode failed: '.$e->getMessage());
        }

        // Force offline mode and test
        $converter->forceOffline(true);
        $this->info('🔌 Testing offline mode...');

        try {
            $offlineRate = $converter->getExchangeRate($base, Currency::PHP);
            $this->info(
                "✅ Offline rate ({$base->value} to PHP): {$offlineRate}",
            );

            // Test conversion
            $amount = 100;
            $converted = $converter->convertAndFormat(
                $amount,
                $base,
                Currency::PHP,
            );
            $this->info(
                "✅ Offline conversion: {$amount} {$base->value} = {$converted}",
            );
        } catch (Exception $e) {
            $this->error('❌ Offline mode failed: '.$e->getMessage());

            return 1;
        }

        $this->info('✅ Offline mode test completed successfully');

        return 0;
    }

    /**
     * Cleanup expired rates
     */
    private function cleanupExpiredRates(CurrencyConverter $converter): int
    {
        $this->info('🧹 Cleaning up expired exchange rates...');

        $deletedCount = $converter->cleanup();

        if ($deletedCount > 0) {
            $this->info("✅ Deleted {$deletedCount} expired rate records");
        } else {
            $this->info('✅ No expired rates to clean up');
        }

        return 0;
    }

    /**
     * Show help information
     */
    private function showHelp(): int
    {
        $this->info('💱 Exchange Rate Management');
        $this->info(str_repeat('=', 30));
        $this->info('Available actions:');
        $this->info('  refresh   - Fetch fresh rates from API');
        $this->info('  status    - Show stored rates statistics');
        $this->info('  export    - Export rates to backup file');
        $this->info('  import    - Import rates from backup file');
        $this->info('  test      - Test offline capabilities');
        $this->info('  cleanup   - Remove expired rates');
        $this->newLine();
        $this->info('Examples:');
        $this->info('  php artisan exchange:rates refresh --base=USD');
        $this->info('  php artisan exchange:rates status --base=EUR');
        $this->info(
            '  php artisan exchange:rates export --base=USD --file=/path/to/backup.json',
        );
        $this->info(
            '  php artisan exchange:rates import --base=USD --file=/path/to/backup.json',
        );
        $this->info(
            '  php artisan exchange:rates test --base=USD --force-offline',
        );

        return 0;
    }
}
