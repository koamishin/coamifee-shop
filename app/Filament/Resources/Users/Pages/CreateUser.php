<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Exception;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Date;

final class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Handle email verification toggle
        if (isset($data['email_verified']) && $data['email_verified']) {
            // If email_verified_at is not set or is a string, convert it to Carbon
            if (! isset($data['email_verified_at'])) {
                $data['email_verified_at'] = now();
            } elseif (is_string($data['email_verified_at'])) {
                try {
                    $data['email_verified_at'] = Date::parse($data['email_verified_at']);
                } catch (Exception) {
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
