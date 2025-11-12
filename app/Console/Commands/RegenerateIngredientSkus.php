<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Ingredient;
use Illuminate\Console\Command;

final class RegenerateIngredientSkus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ingredients:regenerate-skus';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate SKUs for all ingredients that don\'t have one';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Regenerating SKUs for ingredients...');

        $ingredients = Ingredient::query()->whereNull('sku')->get();

        if ($ingredients->isEmpty()) {
            $this->info('All ingredients already have SKUs!');

            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar($ingredients->count());
        $bar->start();

        $regenerated = 0;
        foreach ($ingredients as $ingredient) {
            // Save the ingredient to trigger SKU generation
            $ingredient->save();
            $regenerated++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Successfully generated SKUs for {$regenerated} ingredient(s)!");

        return Command::SUCCESS;
    }
}
