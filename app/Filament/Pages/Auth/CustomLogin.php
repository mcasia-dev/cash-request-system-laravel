<?php
namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Http\Responses\Auth\LoginResponse;
use Filament\Pages\Auth\Login;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CustomLogin extends Login
{
    /**
     * Authenticate the user, blocking login if the account is not approved.
     *
     * @return LoginResponse|null
     * @throws ValidationException When the account is not approved.
     */
    public function authenticate(): ?LoginResponse
    {
        $data = $this->form->getState();

        // Check if account exists and is approved before attempting login
        $user = User::where('email', $data['email'])->first();

        if ($user && $user->status !== 'approved') {
            // Clear password field to avoid resubmit issues
            $this->form->fill(['email' => $data['email'], 'password' => '', 'remember' => $data['remember'] ?? false]);
            throw ValidationException::withMessages([
                'data.email' => 'Account not activated. Please wait for the admin to approved it.',
            ]);
        }

        $credentials = [
            'email'    => $data['email'],
            'password' => $data['password'],
        ];

        if (! Auth::attempt($credentials, $data['remember'] ?? false)) {
            $this->throwFailureValidationException();
        }

        return app(LoginResponse::class);
    }
}
