<?php

declare(strict_types=1);

namespace App\Filament\Resources\IngredientInventories\Pages;

use App\Filament\Resources\IngredientInventories\IngredientInventoryResource;
use App\Models\Ingredient;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

final class EditIngredientInventory extends EditRecord
{
    protected static string $resource = IngredientInventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Check if we need to update the associated ingredient
        if (isset($data['create_new_ingredient']) && $data['create_new_ingredient']) {
            // This shouldn't happen in edit mode, but handle it just in case
            $ingredient = Ingredient::create([
                'name' => $data['new_ingredient_name'],
                'unit_type' => $data['new_ingredient_unit_type'],
            ]);
            $data['ingredient_id'] = $ingredient->id;
        } elseif (isset($data['ingredient_id']) && $record instanceof \App\Models\IngredientInventory && $record->ingredient_id !== $data['ingredient_id']) {
            // Ingredient was changed
            // No assignment needed as data already contains the ingredient_id
        }

        // Remove ingredient creation fields from data
        unset($data['create_new_ingredient'], $data['new_ingredient_name'], $data['new_ingredient_unit_type']);

        return parent::handleRecordUpdate($record, $data);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Set initial values for the form
        $data['create_new_ingredient'] = false;

        return $data;
    }
}
