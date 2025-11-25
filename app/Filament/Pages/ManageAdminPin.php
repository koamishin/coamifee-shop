<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use UnitEnum;

final class ManageAdminPin extends Page implements HasForms
{
    use InteractsWithForms;

    #[Validate('regex:/^\d{4,6}$/', message: 'PIN must be 4-6 digits')]
    public ?string $admin_pin = '';

    #[Validate('same:admin_pin', message: 'The PIN confirmation must match')]
    public ?string $admin_pin_confirmation = '';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-lock-closed';

    protected static UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Manage Admin PIN';

    protected string $view = 'filament.pages.manage-admin-pin';

    public function mount(): void
    {
        $this->form->fill();
    }

    public function submit(): void
    {
        $this->validate();

        Auth::user()->update([
            'admin_pin' => $this->admin_pin,
        ]);

        Notification::make()
            ->success()
            ->title('PIN Updated Successfully')
            ->body('Your admin PIN has been set and is ready to use.')
            ->send();

        $this->admin_pin = '';
        $this->admin_pin_confirmation = '';
        $this->form->fill();
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Admin PIN Management')
                ->description('Set or update your 4-6 digit PIN for sensitive operations like refunds')
                ->schema([
                    Forms\Components\TextInput::make('admin_pin')
                        ->label('Admin PIN')
                        ->password()
                        ->hint('Must be 4-6 digits')
                        ->placeholder('Enter 4-6 digits')
                        ->regex('/^\d{4,6}$/', 'PIN must be 4-6 digits')
                        ->required()
                        ->maxLength(6)
                        ->validationAttribute('admin PIN'),

                    Forms\Components\TextInput::make('admin_pin_confirmation')
                        ->label('Confirm PIN')
                        ->password()
                        ->placeholder('Re-enter your PIN')
                        ->same('admin_pin')
                        ->required()
                        ->maxLength(6),
                ]),
        ];
    }
}
