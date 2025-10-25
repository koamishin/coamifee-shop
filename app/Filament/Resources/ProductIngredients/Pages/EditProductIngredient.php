<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductIngredients\Pages;

use App\Filament\Resources\ProductIngredients\ProductIngredientResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

final class EditProductIngredient extends EditRecord
{
    protected static string $resource = ProductIngredientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
