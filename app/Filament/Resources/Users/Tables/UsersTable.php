<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

final class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-user')
                    ->iconPosition(IconPosition::Before)
                    ->weight('medium'),

                TextColumn::make('email')
                    ->label('Email Address')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-envelope')
                    ->iconPosition(IconPosition::Before)
                    ->copyable()
                    ->copyMessage('Email copied!'),

                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->color('primary')
                    ->separator(',')
                    ->searchable()
                    ->toggleable(),

                IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable()
                    ->alignCenter()
                    ->tooltip(fn (User $record): string => $record->email_verified_at
                        ? 'Verified on '.\Illuminate\Support\Carbon::parse($record->email_verified_at)->format('M d, Y')
                        : 'Not verified'),

                IconColumn::make('two_factor_confirmed_at')
                    ->label('2FA')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-check')
                    ->falseIcon('heroicon-o-shield-exclamation')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->sortable()
                    ->alignCenter()
                    ->tooltip(fn (User $record): string => $record->two_factor_confirmed_at
                        ? 'Enabled on '.\Illuminate\Support\Carbon::parse($record->two_factor_confirmed_at)->format('M d, Y')
                        : 'Not enabled')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->since()
                    ->dateTimeTooltip()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->since()
                    ->dateTimeTooltip()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->label('Filter by Role'),

                TernaryFilter::make('email_verified_at')
                    ->label('Email Verified')
                    ->placeholder('All users')
                    ->trueLabel('Verified only')
                    ->falseLabel('Unverified only')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('email_verified_at'),
                        false: fn (Builder $query) => $query->whereNull('email_verified_at'),
                        blank: fn (Builder $query) => $query,
                    ),

                TernaryFilter::make('two_factor_confirmed_at')
                    ->label('Two-Factor Authentication')
                    ->placeholder('All users')
                    ->trueLabel('2FA enabled')
                    ->falseLabel('2FA disabled')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('two_factor_confirmed_at'),
                        false: fn (Builder $query) => $query->whereNull('two_factor_confirmed_at'),
                        blank: fn (Builder $query) => $query,
                    ),

                Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('created_from')
                            ->label('Created from'),
                        \Filament\Forms\Components\DatePicker::make('created_until')
                            ->label('Created until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->deferFilters()
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                ActionGroup::make([
                    Action::make('verify_email')
                        ->label('Verify Email')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->hidden(fn (User $record): bool => $record->email_verified_at !== null)
                        ->requiresConfirmation()
                        ->action(function (User $record): void {
                            $record->update(['email_verified_at' => now()]);

                            Notification::make()
                                ->title('Email verified')
                                ->success()
                                ->body("Email for {$record->name} has been verified.")
                                ->send();
                        }),

                    Action::make('unverify_email')
                        ->label('Unverify Email')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->hidden(fn (User $record): bool => $record->email_verified_at === null)
                        ->requiresConfirmation()
                        ->action(function (User $record): void {
                            $record->update(['email_verified_at' => null]);

                            Notification::make()
                                ->title('Email unverified')
                                ->warning()
                                ->body("Email for {$record->name} has been unverified.")
                                ->send();
                        }),

                    Action::make('reset_password')
                        ->label('Reset Password')
                        ->icon('heroicon-o-key')
                        ->color('warning')
                        ->form([
                            \Filament\Forms\Components\TextInput::make('new_password')
                                ->label('New Password')
                                ->password()
                                ->required()
                                ->revealable()
                                ->rule(\Illuminate\Validation\Rules\Password::defaults()),
                            \Filament\Forms\Components\TextInput::make('new_password_confirmation')
                                ->label('Confirm Password')
                                ->password()
                                ->required()
                                ->same('new_password')
                                ->revealable(),
                        ])
                        ->requiresConfirmation()
                        ->action(function (User $record, array $data): void {
                            $record->update(['password' => Hash::make($data['new_password'])]);

                            Notification::make()
                                ->title('Password reset')
                                ->success()
                                ->body("Password for {$record->name} has been reset.")
                                ->send();
                        }),

                    Action::make('disable_2fa')
                        ->label('Disable 2FA')
                        ->icon('heroicon-o-shield-exclamation')
                        ->color('danger')
                        ->hidden(fn (User $record): bool => $record->two_factor_confirmed_at === null)
                        ->requiresConfirmation()
                        ->modalDescription('This will disable two-factor authentication for this user.')
                        ->action(function (User $record): void {
                            $record->update([
                                'two_factor_secret' => null,
                                'two_factor_recovery_codes' => null,
                                'two_factor_confirmed_at' => null,
                            ]);

                            Notification::make()
                                ->title('2FA disabled')
                                ->warning()
                                ->body("Two-factor authentication for {$record->name} has been disabled.")
                                ->send();
                        }),

                    DeleteAction::make(),
                ])
                    ->label('Actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),

                    \Filament\Actions\BulkAction::make('verify_email')
                        ->label('Verify Email')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                            $records->each->update(['email_verified_at' => now()]);

                            Notification::make()
                                ->title('Emails verified')
                                ->success()
                                ->body("{$records->count()} user(s) have been verified.")
                                ->send();
                        }),

                    \Filament\Actions\BulkAction::make('assign_role')
                        ->label('Assign Role')
                        ->icon('heroicon-o-shield-check')
                        ->color('primary')
                        ->form([
                            \Filament\Forms\Components\Select::make('role')
                                ->label('Role')
                                ->options(Role::all()->pluck('name', 'name'))
                                ->required(),
                        ])
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data): void {
                            $records->each->assignRole($data['role']);

                            Notification::make()
                                ->title('Role assigned')
                                ->success()
                                ->body("{$records->count()} user(s) have been assigned the {$data['role']} role.")
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->persistSortInSession()
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->striped();
    }
}
