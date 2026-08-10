<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $default = auth()->user()->esAdmin()
            ? route('admin.dashboard', absolute: false)
            : route('dashboard', absolute: false);

        $this->redirectIntended(default: $default, navigate: true);
    }
}; ?>

<div>
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-slate-900">Bienvenido de nuevo</h2>
        <p class="mt-1 text-sm text-slate-500">Inicia sesión para gestionar tus cursos.</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login" class="space-y-5">
        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Correo')" />
            <x-text-input wire:model="form.email" id="email" class="block mt-1 w-full" type="email" name="email" required autofocus autocomplete="username" placeholder="tucorreo@universidad.edu" />
            <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Contraseña')" />

            <x-text-input wire:model="form.password" id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password"
                            placeholder="••••••••" />

            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between">
            <label for="remember" class="inline-flex items-center">
                <input wire:model="form.remember" id="remember" type="checkbox" class="rounded border-slate-300 text-brand-600 shadow-sm focus:ring-brand-500" name="remember">
                <span class="ms-2 text-sm text-slate-600">{{ __('Recordarme') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm font-medium text-brand-600 hover:text-brand-700" href="{{ route('password.request') }}" wire:navigate>
                    {{ __('¿Olvidaste tu contraseña?') }}
                </a>
            @endif
        </div>

        <x-primary-button class="w-full">
            {{ __('Iniciar sesión') }}
        </x-primary-button>

        <p class="text-center text-sm text-slate-500">
            {{ __('¿No tienes cuenta?') }}
            <a href="{{ route('register') }}" wire:navigate class="font-semibold text-brand-600 hover:text-brand-700">{{ __('Regístrate') }}</a>
        </p>
    </form>
</div>
