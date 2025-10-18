<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

final class ProjectInstall extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'project:install
                            {--force : Force installation even if already installed}
                            {--env= : Environment to setup (local, staging, production)}
                            {--seed : Run database seeding after installation}
                            {--demo : Install with demo data}
                            {--fresh : Fresh install (drop existing tables)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install and configure the Coffee Shop Management System';

    /**
     * Installation steps and their descriptions.
     */
    private array $steps = [
        'checkEnvironment' => 'Checking environment requirements',
        'setupDatabase' => 'Setting up database',
        'createStorageLinks' => 'Creating storage links',
        'generateAppKey' => 'Generating application key',
        'publishVendorAssets' => 'Publishing vendor assets',
        'runMigrations' => 'Running database migrations',
        'seedDatabase' => 'Seeding database with initial data',
        'optimizeApplication' => 'Optimizing application',
        'configurePermissions' => 'Setting file permissions',
        'installFrontend' => 'Installing frontend dependencies',
        'buildFrontend' => 'Building frontend assets',
    ];

    /**
     * Installation configuration.
     */
    private array $config = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->displayHeader();

        if ($this->isAlreadyInstalled() && ! $this->option('force')) {
            $this->error('🚫 Project is already installed. Use --force to reinstall.');
            $this->info('💡 If you want to reinstall, run: php artisan project:install --force');

            return Command::FAILURE;
        }

        if (! $this->confirmInstallation()) {
            $this->info('Installation cancelled.');

            return Command::SUCCESS;
        }

        $this->config = $this->gatherConfiguration();

        $this->newLine();
        $this->info('🚀 Starting Coffee Shop Management System installation...');
        $this->newLine();

        try {
            $this->runInstallationSteps();
            $this->displayCompletionMessage();

            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->error('❌ Installation failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Display installation header.
     */
    private function displayHeader(): void
    {
        $this->newLine();
        $this->info('☕ Coffee Shop Management System Installer');
        $this->info('================================================');
        $this->newLine();
    }

    /**
     * Check if project is already installed.
     */
    private function isAlreadyInstalled(): bool
    {
        return File::exists(storage_path('app/installed')) &&
               File::exists(storage_path('app/installation.json'));
    }

    /**
     * Confirm installation with user.
     */
    private function confirmInstallation(): bool
    {
        if ($this->option('force')) {
            $this->warn('⚠️  Force mode enabled - will overwrite existing installation');
        }

        return $this->confirm('Do you want to continue with the installation?');
    }

    /**
     * Gather installation configuration from user.
     */
    private function gatherConfiguration(): array
    {
        $config = [
            'environment' => $this->option('env') ?? $this->choice(
                'Select environment',
                ['local', 'staging', 'production'],
                'local'
            ),
            'database_host' => $this->ask('Database host', env('DB_HOST', '127.0.0.1')),
            'database_port' => $this->ask('Database port', env('DB_PORT', '3306')),
            'database_name' => $this->ask('Database name', env('DB_DATABASE', 'coamifee_shop')),
            'database_user' => $this->ask('Database username', env('DB_USERNAME', 'root')),
            'database_password' => $this->secret('Database password (leave empty if none)'),
            'app_url' => $this->ask('Application URL', env('APP_URL', 'http://localhost:8000')),
            'app_name' => $this->ask('Application name', 'Coffee Shop Management'),
            'admin_email' => $this->ask('Admin email', 'admin@coamifee-shop.com'),
            'admin_password' => $this->secret('Admin password', 'password'),
        ];

        if ($this->option('demo')) {
            $config['seed_demo_data'] = true;
        } else {
            $config['seed_demo_data'] = $this->confirm('Install demo data?');
        }

        return $config;
    }

    /**
     * Run all installation steps.
     */
    private function runInstallationSteps(): void
    {
        $progressBar = $this->output->createProgressBar(count($this->steps));
        $progressBar->start();

        foreach ($this->steps as $step => $description) {
            $this->line($description);
            $this->$step();
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
    }

    /**
     * Check environment requirements.
     */
    private function checkEnvironment(): void
    {
        // Check PHP version
        if (version_compare(PHP_VERSION, '8.2.0', '<')) {
            throw new Exception('PHP 8.2 or higher is required');
        }

        // Check required PHP extensions
        $requiredExtensions = ['pdo', 'pdo_mysql', 'mbstring', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath', 'openssl'];
        foreach ($requiredExtensions as $extension) {
            if (! extension_loaded($extension)) {
                throw new Exception("PHP extension '{$extension}' is required");
            }
        }

        // Check if composer is available
        if (! shell_exec('which composer')) {
            throw new Exception('Composer is required');
        }

        // Check if npm is available
        if (! shell_exec('which npm')) {
            $this->warn('⚠️  npm is not available. Frontend assets will not be built.');
        }

        $this->info('✓ Environment requirements satisfied');
    }

    /**
     * Setup database configuration.
     */
    private function setupDatabase(): void
    {
        $envContent = File::get('.env');

        // Update database configuration
        $envContent = preg_replace('/^DB_CONNECTION=.*/m', 'DB_CONNECTION=mysql', $envContent);
        $envContent = preg_replace('/^DB_HOST=.*/m', 'DB_HOST='.$this->config['database_host'], $envContent);
        $envContent = preg_replace('/^DB_PORT=.*/m', 'DB_PORT='.$this->config['database_port'], $envContent);
        $envContent = preg_replace('/^DB_DATABASE=.*/m', 'DB_DATABASE='.$this->config['database_name'], $envContent);
        $envContent = preg_replace('/^DB_USERNAME=.*/m', 'DB_USERNAME='.$this->config['database_user'], $envContent);
        $envContent = preg_replace('/^DB_PASSWORD=.*/m', 'DB_PASSWORD='.$this->config['database_password'], $envContent);

        // Update application configuration
        $envContent = preg_replace('/^APP_ENV=.*/m', 'APP_ENV='.$this->config['environment'], $envContent);
        $envContent = preg_replace('/^APP_URL=.*/m', 'APP_URL='.$this->config['app_url'], $envContent);
        $envContent = preg_replace('/^APP_NAME=.*/m', 'APP_NAME="'.$this->config['app_name'].'"', $envContent);

        File::put('.env', $envContent);

        // Test database connection
        try {
            DB::connection()->getPdo();
            $this->info('✓ Database connection established');
        } catch (Exception $e) {
            throw new Exception('Database connection failed: '.$e->getMessage());
        }
    }

    /**
     * Create storage symbolic links.
     */
    private function createStorageLinks(): void
    {
        Artisan::call('storage:link', [], $this->output);
        $this->info('✓ Storage links created');
    }

    /**
     * Generate application key.
     */
    private function generateAppKey(): void
    {
        Artisan::call('key:generate', ['--force' => true], $this->output);
        $this->info('✓ Application key generated');
    }

    /**
     * Publish vendor assets.
     */
    private function publishVendorAssets(): void
    {
        Artisan::call('vendor:publish', [
            '--tag' => 'filament-config',
            '--force' => true,
        ], $this->output);

        Artisan::call('vendor:publish', [
            '--tag' => 'filament-assets',
            '--force' => true,
        ], $this->output);

        $this->info('✓ Vendor assets published');
    }

    /**
     * Run database migrations.
     */
    private function runMigrations(): void
    {
        $options = ['--force' => true];
        if ($this->option('fresh')) {
            Artisan::call('migrate:fresh', $options, $this->output);
        } else {
            Artisan::call('migrate', $options, $this->output);
        }

        $this->info('✓ Database migrations completed');
    }

    /**
     * Seed database with initial data.
     */
    private function seedDatabase(): void
    {
        if ($this->option('seed') || $this->config['seed_demo_data']) {
            Artisan::call('db:seed', ['--force' => true], $this->output);
            $this->info('✓ Database seeded with initial data');
        } else {
            $this->info('✓ Database seeding skipped');
        }
    }

    /**
     * Optimize application.
     */
    private function optimizeApplication(): void
    {
        Artisan::call('config:cache', [], $this->output);
        Artisan::call('route:cache', [], $this->output);
        Artisan::call('view:cache', [], $this->output);

        $this->info('✓ Application optimized');
    }

    /**
     * Configure file permissions.
     */
    private function configurePermissions(): void
    {
        $directories = [
            storage_path(),
            base_path('bootstrap/cache'),
        ];

        foreach ($directories as $directory) {
            if (is_dir($directory)) {
                $this->setPermissions($directory, 775, 664);
            }
        }

        $this->info('✓ File permissions configured');
    }

    /**
     * Set permissions for directory and files.
     */
    private function setPermissions(string $path, int $dirPermissions, int $filePermissions): void
    {
        if (is_dir($path)) {
            chmod($path, $dirPermissions);
            $items = scandir($path);

            foreach (array_diff($items, ['.', '..']) as $item) {
                $fullPath = $path.DIRECTORY_SEPARATOR.$item;

                if (is_dir($fullPath)) {
                    $this->setPermissions($fullPath, $dirPermissions, $filePermissions);
                } else {
                    chmod($fullPath, $filePermissions);
                }
            }
        }
    }

    /**
     * Install frontend dependencies.
     */
    private function installFrontend(): void
    {
        if (shell_exec('which npm')) {
            $this->info('Installing npm dependencies...');
            shell_exec('npm install');
            $this->info('✓ Frontend dependencies installed');
        } else {
            $this->warn('⚠️  npm not available. Skipping frontend installation.');
        }
    }

    /**
     * Build frontend assets.
     */
    private function buildFrontend(): void
    {
        if (shell_exec('which npm')) {
            $this->info('Building frontend assets...');
            shell_exec('npm run build');
            $this->info('✓ Frontend assets built');
        } else {
            $this->warn('⚠️  npm not available. Skipping frontend build.');
        }
    }

    /**
     * Display completion message.
     */
    private function displayCompletionMessage(): void
    {
        $this->newLine();
        $this->info('🎉 Installation completed successfully!');
        $this->newLine();

        $this->displayNextSteps();

        // Save installation record
        $this->saveInstallationRecord();
    }

    /**
     * Display next steps to the user.
     */
    private function displayNextSteps(): void
    {
        $this->info('📋 Next Steps:');
        $this->line('1. Start the development server: php artisan serve');
        $this->line('2. Access the admin panel: '.$this->config['app_url'].'/admin');
        $this->line('3. Login with admin credentials:');
        $this->line('   - Email: '.$this->config['admin_email']);
        $this->line('   - Password: '.$this->config['admin_password']);
        $this->newLine();

        $this->info('🔧 Useful Commands:');
        $this->line('• Run tests: php artisan test');
        $this->line('• Clear cache: php artisan optimize:clear');
        $this->line('• View routes: php artisan route:list');
        $this->line('• Create user: php artisan make:user');

        if ($this->config['environment'] === 'production') {
            $this->newLine();
            $this->warn('⚠️  Production Environment:');
            $this->line('• Configure cron jobs for scheduling');
            $this->line('• Set up queue workers');
            $this->line('• Configure SSL certificates');
            $this->line('• Set up backups');
        }
    }

    /**
     * Save installation record.
     */
    private function saveInstallationRecord(): void
    {
        $installationData = [
            'installed_at' => now()->toISOString(),
            'environment' => $this->config['environment'],
            'app_url' => $this->config['app_url'],
            'app_name' => $this->config['app_name'],
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'database_connection' => DB::connection()->getDriverName(),
        ];

        File::ensureDirectoryExists(storage_path('app'));
        File::put(storage_path('app/installation.json'), json_encode($installationData, JSON_PRETTY_PRINT));
        File::put(storage_path('app/installed'), '1');
    }
}
