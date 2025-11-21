<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class TestR2StorageCommand extends Command
{
    protected $signature = 'storage:test-r2 {--cleanup : Clean up test files after testing}';

    protected $description = 'Test R2 storage functionality including upload, download, and deletion';

    public function handle(): int
    {
        $this->info('Testing R2 storage functionality...');
        $this->line('');

        $disk = 'r2';
        $testContent = 'This is a test file for R2 storage verification. Generated at: '.now()->toISOString();
        $testFileName = 'test-'.Str::uuid().'.txt';
        $cleanup = $this->option('cleanup');

        try {
            // Test 0: Check environment variables first
            $this->info('0. Checking R2 environment configuration...');
            $requiredVars = ['R2_ACCESS_KEY_ID', 'R2_SECRET_ACCESS_KEY', 'R2_BUCKET', 'R2_ENDPOINT'];
            $missingVars = [];

            foreach ($requiredVars as $var) {
                if (empty(env($var))) {
                    $missingVars[] = $var;
                }
            }

            if (! empty($missingVars)) {
                $this->error('Missing required R2 environment variables: '.implode(', ', $missingVars));
                $this->line('Please add these variables to your .env file.');

                return 1;
            }

            if (empty(env('R2_DEFAULT_REGION'))) {
                $this->warn('R2_DEFAULT_REGION is not set. For Cloudflare R2, this should typically be "auto" or left empty.');
                $this->line('Setting region to "auto" for the test...');
                config(['filesystems.disks.r2.region' => 'auto']);
            }

            $this->info('✓ R2 environment variables are configured');
            $this->line('');

            // Test 1: Check if R2 disk exists and is configured
            $this->info('1. Checking R2 disk configuration...');
            try {
                $storage = Storage::disk($disk);
                $this->info('✓ R2 disk is configured');
            } catch (Exception $e) {
                $this->error('R2 disk configuration error: '.$e->getMessage());

                return 1;
            }
            $this->line('');

            // Test 2: Upload file to R2
            $this->info('2. Testing file upload to R2...');
            $uploadSuccess = Storage::disk($disk)->put($testFileName, $testContent);

            if (! $uploadSuccess) {
                $this->error('Failed to upload file to R2.');

                return 1;
            }
            $this->info('✓ File uploaded successfully to R2');
            $this->info("  File: {$testFileName}");
            $this->line('');

            // Test 3: Check if file exists
            $this->info('3. Verifying file existence...');
            if (! Storage::disk($disk)->exists($testFileName)) {
                $this->error('File does not exist in R2 after upload.');

                return 1;
            }
            $this->info('✓ File exists in R2');
            $this->line('');

            // Test 4: Get file size
            $this->info('4. Checking file size...');
            $fileSize = Storage::disk($disk)->size($testFileName);
            $this->info("✓ File size: {$fileSize} bytes");
            $this->line('');

            // Test 5: Download file
            $this->info('5. Testing file download...');
            $downloadedContent = Storage::disk($disk)->get($testFileName);

            if ($downloadedContent !== $testContent) {
                $this->error('Downloaded content does not match original content.');

                return 1;
            }
            $this->info('✓ File downloaded successfully with matching content');
            $this->line('');

            // Test 6: Get file URL (if configured)
            $this->info('6. Testing URL generation...');
            try {
                $url = Storage::disk($disk)->url($testFileName);
                $this->info("✓ URL generated: {$url}");
            } catch (Exception $e) {
                $this->warn('⚠ URL generation failed (this might be expected for private buckets): '.$e->getMessage());
            }
            $this->line('');

            // Test 7: List files (optional test)
            $this->info('7. Testing file listing...');
            try {
                $files = Storage::disk($disk)->files();
                $foundTestFile = in_array($testFileName, $files);
                $this->info($foundTestFile ? '✓ Test file found in directory listing' : '⚠ Test file not found in directory listing');
            } catch (Exception $e) {
                $this->warn('⚠ File listing failed: '.$e->getMessage());
            }
            $this->line('');

            // Test 8: Delete file (always cleanup test file)
            $this->info('8. Testing file deletion...');
            $deleteSuccess = Storage::disk($disk)->delete($testFileName);

            if (! $deleteSuccess) {
                $this->error('Failed to delete file from R2.');

                return 1;
            }
            $this->info('✓ File deleted successfully from R2');
            $this->line('');

            // Cleanup confirmation
            if ($cleanup) {
                $this->info('🧹 Cleanup option was specified - test file has been removed');
            }

            $this->info('🎉 All R2 storage tests passed successfully!');

            return 0;

        } catch (Exception $e) {
            $this->error('❌ Test failed with exception: '.$e->getMessage());
            $this->error('Stack trace: '.$e->getTraceAsString());

            // Attempt cleanup on failure
            try {
                if (isset($testFileName) && Storage::disk($disk)->exists($testFileName)) {
                    Storage::disk($disk)->delete($testFileName);
                    $this->info('🧹 Clean up test file after failure');
                }
            } catch (Exception $cleanupException) {
                $this->warn('⚠ Cleanup failed: '.$cleanupException->getMessage());
            }

            return 1;
        }
    }
}
