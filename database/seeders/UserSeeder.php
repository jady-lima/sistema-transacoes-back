<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@email.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make(hash('sha256', '12345678')),
                'role' => 'admin',
            ]
        );

        User::updateOrCreate(
            ['email' => 'cliente@email.com'],
            [
                'name' => 'Cliente um',
                'password' => Hash::make(hash('sha256', '12345678')),
                'role' => 'cliente',
            ]
        );

        User::updateOrCreate(
            ['email' => 'cliente2@email.com'],
            [
                'name' => 'Cliente Dois',
                'password' => Hash::make(hash('sha256', '12345678')),
                'role' => 'cliente',
            ]
        );
    }
}
