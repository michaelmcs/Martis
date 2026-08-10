<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@sistema.edu'],
            [
                'name' => 'Administrador',
                'dni' => '00000000',
                'rol' => User::ROL_ADMIN,
                'password' => Hash::make('Admin123!'),
            ],
        );
    }
}
