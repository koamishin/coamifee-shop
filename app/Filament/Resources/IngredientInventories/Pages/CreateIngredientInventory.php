<?php

declare(strict_types=1);

namespace App\Filament\Resources\IngredientInventories\Pages;

use App\Filament\Resources\IngredientInventories\IngredientInventoryResource;
use App\Models\Ingredient;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateIngredientInventory extends CreateRecord
{
    protected static string $resource = IngredientInventoryResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $ingredientId = null;

        // Check if we need to create a new ingredient
        if (isset($data['create_new_ingredient']) && $data['create_new_ingredient']) {
            $ingredient = Ingredient::create([
                'name' => $data['new_ingredient_name'],
                'unit_type' => $data['new_ingredient_unit_type'],
            ]);
            $ingredientId = $ingredient->id;
        } else {
            $ingredientId = $data['ingredient_id'];
        }

        // Remove ingredient creation fields from data
        unset($data['create_new_ingredient'], $data['new_ingredient_name'], $data['new_ingredient_unit_type']);

        // Set the ingredient ID
        $data['ingredient_id'] = $ingredientId;

        return parent::handleRecordCreation($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
