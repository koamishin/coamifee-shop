<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductIngredients\Pages;

use App\Filament\Resources\ProductIngredients\ProductIngredientResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

final class ViewProductIngredient extends ViewRecord
{
    protected static string $resource = ProductIngredientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
