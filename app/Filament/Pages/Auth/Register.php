<?php
namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Forms\Form;
use App\Models\Department;
use Illuminate\Support\Str;
use App\Jobs\ConfirmRegistrationJob;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Validation\Rules\Password as PasswordRule;

class Register extends BaseRegister
{
    public function form(Form $schema): Form
    {
        return parent::form($schema)
            ->components([
                $this->getControlNumberFormComponent(),
                $this->getNameFormComponent(),
                $this->getFirstNameFormComponent(),
                $this->getMiddleNameFormComponent(),
                $this->getLastNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getDepartmentFormComponent(),
                $this->getDepartmentHeadFormComponent(),
                $this->getPhoneFormComponent(),
                $this->getPositionFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                $this->getSignatureNumberFormComponent(),
                $this->getTermsFormComponent(),
            ]);
    }

    protected function getControlNumberFormComponent()
    {
        $last_id     = User::latest()->first()->id ?? 0;
        $tracking_no = 'MCA-2025-' . str_pad($last_id + 1, 4, '0', STR_PAD_LEFT);

        return Hidden::make('control_no')
            ->default($tracking_no)
            ->required();
    }

    protected function getNameFormComponent(): Component
    {
        return Hidden::make('name')
            ->default(fn() => '')
            ->dehydrated();
    }

    protected function getFirstNameFormComponent(): Component
    {
        return TextInput::make('first_name')
            ->label('First Name')
            ->required()
            ->maxLength(255)
            ->autofocus();
    }

    protected function getMiddleNameFormComponent(): Component
    {
        return TextInput::make('middle_name')
            ->label('Middle Name')
            ->nullable()
            ->maxLength(255);
    }

    protected function getLastNameFormComponent(): Component
    {
        return TextInput::make('last_name')
            ->label('Last Name')
            ->required()
            ->maxLength(255);
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Company Email Address')
            ->rules(['required', 'email', 'regex:/^[a-zA-Z0-9._%+-]+@mcasiafoodtrade\.ph$/'])
            ->email()
            ->maxLength(255)
            ->unique($this->getUserModel());
    }

    protected function getDepartmentFormComponent()
    {
        return Select::make('department_id')
            ->label('Department')
            ->options(Department::whereNotNull('department_name')->pluck('department_name', 'id')->toArray())
            ->required()
            ->live()
            ->afterStateUpdated(function ($state, callable $set) {
                if ($state) {
                    $departmentHead = Department::find($state)->department_head ?? null;
                    $set('department_head', $departmentHead);
                }
            });
    }

    protected function getDepartmentHeadFormComponent()
    {
        return TextInput::make('department_head')
            ->label('Department Head')
            ->default(fn($get) => $get('department') ? Department::find($get('department'))->department_head ?? null : null)
            ->required()
            ->disabled();
    }

    protected function getPhoneFormComponent(): Component
    {
        return TextInput::make('contact_number')
            ->label('Mobile Number')
            ->tel()
            ->prefix('+63')
            ->rules(['required'])
            ->placeholder('9123456789')
            ->maxLength(10)
            ->unique($this->getUserModel());
    }

    protected function getPositionFormComponent(): Component
    {
        return TextInput::make('position')
            ->label('Position')
            ->required()
            ->maxLength(255);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->rules([
                'required',
                PasswordRule::min(8)
                    ->mixedCase() // uppercase + lowercase
                    ->letters()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ])
            // ->showAllValidationMessages()
            ->dehydrateStateUsing(fn($state) => Hash::make($state))
            ->same('passwordConfirmation');
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return TextInput::make('passwordConfirmation')
            ->label('Confirm Password')
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->dehydrated(false);
    }

    private function generateAlphanumeric($length = 6)
    {
        $characters       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $charactersLength = Str::length($characters);
        $randomString     = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    protected function getSignatureNumberFormComponent()
    {
        return Hidden::make('signature_number')
            ->default(fn() => $this->generateAlphanumeric(12))
            ->required();
    }

    protected function getTermsFormComponent(): Component
    {
        return Checkbox::make('terms')
            ->label('I agree to the terms and conditions')
            ->required()
            ->accepted()
            ->dehydrated(false);
    }

    /**
     * Remove the 'terms' field from data before registration
     * since it's only used for validation, not storage.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeRegister(array $data): array
    {
        // Remove terms field as it's only for validation
        unset($data['terms']);

        // Concatenate first_name and last_name into name field
        $firstName    = $data['first_name'] ?? '';
        $lastName     = $data['last_name'] ?? '';
        $data['name'] = trim("{$firstName} {$lastName}");

        return $data;
    }

    /**
     * Hook called after user registration is complete.
     * This method is called via Filament's hook system.
     * You can add custom logic here like sending welcome emails,
     * assigning roles, etc.
     *
     * To use this hook, you can also listen to the Registered event
     * in your EventServiceProvider or use Filament's hook system.
     */
    protected function afterRegister(): void
    {
        // This hook is called after registration
        // You can access the registered user via the form model
        // Example: $user = $this->form->getRecord();

        ConfirmRegistrationJob::dispatch($this->form->getRecord());

        // Example: You could assign a default role here
        // $user->assignRole('user');
    }
}
