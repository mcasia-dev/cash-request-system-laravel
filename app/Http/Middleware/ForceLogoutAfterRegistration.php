<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ForceLogoutAfterRegistration
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->status !== 'approved') {
            Auth::logout();

            return redirect()
                ->to(Filament::getLoginUrl())
                ->withErrors([
                    'email' => 'Account not activated yet. Please wait for admin approval.',
                ]);
        }

        return $next($request);
    }
}
