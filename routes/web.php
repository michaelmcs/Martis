<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route(auth()->user()->esAdmin() ? 'admin.dashboard' : 'dashboard');
    }

    return redirect()->route('login');
});

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware(['auth'])->group(function () {
    Volt::route('instituciones', 'instituciones.index')->name('instituciones.index');
    Volt::route('cursos', 'cursos.index')->name('cursos.index');
    Volt::route('cursos/{cursoId}/gestionar', 'cursos.gestionar')->name('cursos.gestionar');

    Route::get('cursos/{cursoId}/calendario.ics', function (string $cursoId) {
        $curso = auth()->user()->cursos()->findOrFail($cursoId);
        $ics = \App\Support\IcsGenerator::paraCurso($curso);

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="calendario_'.str($curso->nombre)->slug().'.ics"',
        ]);
    })->name('cursos.calendario');
    Volt::route('estudiantes', 'estudiantes.index')->name('estudiantes.index');
    Volt::route('asistente-ia', 'asistente.index')->name('asistente.index');
});

Route::middleware(['auth', 'can:admin'])->prefix('admin')->name('admin.')->group(function () {
    Volt::route('panel', 'admin.dashboard')->name('dashboard');
    Volt::route('docentes', 'admin.docentes')->name('docentes');
});

require __DIR__.'/auth.php';
