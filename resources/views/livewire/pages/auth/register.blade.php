<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $apellidos = '';
    public string $dni = '';
    public string $celular = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],
            'dni' => ['required', 'string', 'max:20', 'unique:'.User::class],
            'celular' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        event(new Registered($user = User::create($validated)));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-slate-900">Crea tu cuenta</h2>
        <p class="mt-1 text-sm text-slate-500">Empieza a gestionar tus cursos hoy mismo.</p>
    </div>

    <form wire:submit="register" class="space-y-5">
        <div class="grid sm:grid-cols-2 gap-4">
            <!-- Nombres -->
            <div>
                <x-input-label for="name" :value="__('Nombres')" />
                <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" name="name" required autofocus autocomplete="given-name" placeholder="Ej. Juan Carlos" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <!-- Apellidos -->
            <div>
                <x-input-label for="apellidos" :value="__('Apellidos')" />
                <x-text-input wire:model="apellidos" id="apellidos" class="block mt-1 w-full" type="text" name="apellidos" required autocomplete="family-name" placeholder="Ej. Pérez Gómez" />
                <x-input-error :messages="$errors->get('apellidos')" class="mt-2" />
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <!-- DNI -->
            <div>
                <x-input-label for="dni" :value="__('DNI')" />
                <x-text-input wire:model="dni" id="dni" class="block mt-1 w-full" type="text" name="dni" required autocomplete="off" placeholder="Documento de identidad" />
                <x-input-error :messages="$errors->get('dni')" class="mt-2" />
            </div>

            <!-- Celular -->
            <div>
                <x-input-label for="celular" :value="__('Celular')" />
                <x-text-input wire:model="celular" id="celular" class="block mt-1 w-full" type="text" name="celular" autocomplete="tel" placeholder="Ej. 987 654 321" />
                <x-input-error :messages="$errors->get('celular')" class="mt-2" />
            </div>
        </div>

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Correo')" />
            <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" name="email" required autocomplete="username" placeholder="tucorreo@universidad.edu" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Contraseña')" />

            <x-text-input wire:model="password" id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password"
                            placeholder="••••••••" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-input-label for="password_confirmation" :value="__('Confirmar contraseña')" />

            <x-text-input wire:model="password_confirmation" id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password"
                            placeholder="••••••••" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <x-primary-button class="w-full">
            {{ __('Crear cuenta') }}
        </x-primary-button>

        <p class="text-center text-sm text-slate-500">
            {{ __('¿Ya tienes cuenta?') }}
            <a href="{{ route('login') }}" wire:navigate class="font-semibold text-brand-600 hover:text-brand-700">{{ __('Inicia sesión') }}</a>
        </p>
    </form>
</div>
