<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Pages\Auth\Login;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Filament\Http\Responses\Auth\LoginResponse;

class CustomLogin extends Login
{
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
