<?php

namespace App\Filament\Pages\Auth;

use App\Models\Department;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;

class EditProfile extends BaseEditProfile
{
    protected ?string $maxWidth = MaxWidth::ThreeExtraLarge->value;

    public static function getLabel(): string
    {
        return 'My Profile';
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            SpatieMediaLibraryFileUpload::make('profile_photo')
                ->label('Profile Photo')
                ->collection('profile')
                ->image()
                ->imageEditor()
                ->avatar()
                ->columnSpanFull(),

            TextInput::make('control_no')
                ->label('Control No.')
                ->disabled()
                ->dehydrated(false),

            TextInput::make('first_name')
                ->label('First Name')
                ->required()
                ->maxLength(255),

            TextInput::make('middle_name')
                ->label('Middle Name')
                ->maxLength(255),

            TextInput::make('last_name')
                ->label('Last Name')
                ->required()
                ->maxLength(255),

            $this->getEmailFormComponent(),

            TextInput::make('position')
                ->label('Position')
                ->required()
                ->maxLength(255),

            TextInput::make('contact_number')
                ->label('Mobile Number')
                ->tel()
                ->prefix('+63')
                ->placeholder('9123456789')
                ->maxLength(10)
                ->required()
                ->unique(ignoreRecord: true),

            Select::make('department_id')
                ->label('Department')
                ->options(Department::query()->pluck('department_name', 'id')->toArray())
                ->searchable()
                ->preload()
                ->required(),

            $this->getPasswordFormComponent(),
            $this->getPasswordConfirmationFormComponent(),
        ])->columns(2);
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Company Email Address')
            ->email()
            ->rules(['required', 'email', 'regex:/^[a-zA-Z0-9._%+-]+@mcasiafoodtrade\.ph$/'])
            ->maxLength(255)
            ->required()
            ->unique(ignoreRecord: true);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('New Password')
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->rules([
                PasswordRule::min(8)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ])
            ->autocomplete('new-password')
            ->dehydrated(fn($state): bool => filled($state))
            ->dehydrateStateUsing(fn($state): string => Hash::make($state))
            ->same('passwordConfirmation');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $firstName = trim((string) ($data['first_name'] ?? ''));
        $lastName  = trim((string) ($data['last_name'] ?? ''));

        $data['name'] = trim($firstName . ' ' . $lastName);

        return $data;
    }
}
