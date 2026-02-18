<x-filament-panels::page.simple class="custom-register-page">
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_REGISTER_FORM_BEFORE, scopes: $this->getRenderHookScopes()) }}

    <div class="register-brand">
        <img src="{{ asset('logo.png') }}" alt="MC Asia Foodtrade Corporation logo">
        <span>CASH REQUEST SYSTEM</span>
    </div>

    <h1 class="register-title">Create account</h1>

    <x-filament-panels::form id="form" wire:submit="register" class="register-form">
        {{ $this->form }}

        <x-filament::button type="submit" form="form" size="lg" color="danger" class="register-submit">
            Create account
        </x-filament::button>
    </x-filament-panels::form>

    @if (filament()->hasLogin())
        <p class="register-login-link">
            Already have an account?
            {{ $this->loginAction }}
        </p>
    @endif

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_REGISTER_FORM_AFTER, scopes: $this->getRenderHookScopes()) }}

    <style>
        .custom-register-page {
            --register-text: #0f2548;
            --register-border: #c9d4e2;
            --register-muted: #64748b;
        }

        .custom-register-page .fi-simple-main {
            gap: 1rem;
        }

        .custom-register-page .register-brand {
            align-items: center;
            display: flex;
            gap: 0.75rem;
            margin-bottom: 0.25rem;
        }

        .custom-register-page .register-brand img {
            border: 1px solid #d5dbe5;
            border-radius: 0.5rem;
            height: 78px;
            object-fit: contain;
            padding: 0.35rem;
            width: 86px;
        }

        .custom-register-page .register-brand span {
            color: #3e5f87;
            font-size: 1rem;
            letter-spacing: 0.16em;
        }

        .custom-register-page .register-title {
            color: var(--register-text);
            font-size: 3rem;
            font-weight: 800;
            line-height: 1.05;
            margin: 0.5rem 0 1rem;
        }

        .custom-register-page .register-form {
            gap: 0.65rem;
        }

        .custom-register-page .fi-fo-field-wrp-label span {
            color: var(--register-text);
            font-size: 1.05rem;
            font-weight: 700;
        }

        .custom-register-page .fi-input,
        .custom-register-page .fi-select-input {
            border-color: var(--register-border);
            border-radius: 0.8rem;
            border-width: 1px;
            box-shadow: none !important;
            min-height: 3rem;
        }

        .custom-register-page .fi-input-wrp {
            border: 1px solid var(--register-border) !important;
            box-shadow: none !important;
        }

        .custom-register-page .fi-input-wrp:focus-within {
            border: 1px solid #b9c8dc !important;
            box-shadow: none !important;
        }

        .custom-register-page .fi-input:focus,
        .custom-register-page .fi-select-input:focus,
        .custom-register-page .fi-input:focus-visible,
        .custom-register-page .fi-select-input:focus-visible {
            box-shadow: none !important;
        }

        .custom-register-page .fi-input::placeholder {
            color: var(--register-muted);
        }

        .custom-register-page .fi-fo-checkbox-list {
            margin-top: 0.5rem;
        }

        .custom-register-page .fi-fo-checkbox-list-label {
            color: var(--register-text);
            font-weight: 700;
        }

        .custom-register-page .register-submit {
            margin-top: 0.3rem;
            width: 100%;
        }

        .custom-register-page .register-login-link {
            color: var(--register-muted);
            font-size: 1.2rem;
            margin: 0.5rem 0 0;
        }

        .custom-register-page .register-login-link .fi-link {
            color: #e11d2a;
            font-weight: 700;
            text-decoration: none !important;
        }

        @media (max-width: 768px) {
            .custom-register-page .register-title {
                font-size: 2.3rem;
            }

            .custom-register-page .register-brand span {
                font-size: 0.9rem;
                letter-spacing: 0.08em;
            }
        }
    </style>
</x-filament-panels::page.simple>
