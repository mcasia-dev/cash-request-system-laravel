<?php
namespace App\Filament\Pages\Auth;

use App\Jobs\ConfirmRegistrationJob;
use App\Models\Department;
use App\Models\User;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class Register extends BaseRegister
{
    public function form(Form $schema): Form
    {
        return parent::form($schema)
            ->components([
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
                $this->getTermsFormComponent(),
            ]);
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
            ->searchable()
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
        $user = $this->form->getRecord();

        ConfirmRegistrationJob::dispatch($user);

        $departmentHeads = User::query()
            ->role('department_head')
            ->where('department_id', $user->department_id)
            ->get();

        if ($departmentHeads->isNotEmpty()) {
            Notification::make()
                ->title('New User Registration')
                ->body("{$user->name} has registered and is waiting for your approval.")
                ->actions([
                    Action::make('markAsRead')
                        ->button()
                        ->markAsRead(),

                    Action::make('view')
                        ->link()
                        ->url(route('filament.admin.resources.user-request-approval.view', ['record' => $user->id])),

                ])
                ->sendToDatabase($departmentHeads)
                ->toDatabase();
        }

        // $user->assignRole('user');
    }
}
