<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Exception;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

final class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Handle email verification toggle
        if (isset($data['email_verified']) && $data['email_verified']) {
            // If email_verified_at is not set or is a string, convert it to Carbon
            if (! isset($data['email_verified_at'])) {
                $data['email_verified_at'] = now();
            } elseif (is_string($data['email_verified_at'])) {
                try {
                    $data['email_verified_at'] = \Illuminate\Support\Carbon::parse($data['email_verified_at']);
                } catch (Exception $e) {
                    $data['email_verified_at'] = now();
                }
            }
        } elseif (isset($data['email_verified']) && ! $data['email_verified']) {
            $data['email_verified_at'] = null;
        }

        // Remove the email_verified field as it's not a database column
        unset($data['email_verified']);

        return $data;
    }
}
