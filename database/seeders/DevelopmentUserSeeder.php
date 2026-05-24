<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DevelopmentUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@pollamundial.test'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
                'is_activated' => true,
                'coins_balance' => 50,
            ]
        );

        $users = [
            ['name' => 'Juan',   'email' => 'juan@pollamundial.test'],
            ['name' => 'María',  'email' => 'maria@pollamundial.test'],
            ['name' => 'Carlos', 'email' => 'carlos@pollamundial.test'],
            ['name' => 'Ana',    'email' => 'ana@pollamundial.test'],
            ['name' => 'Luis',   'email' => 'luis@pollamundial.test'],
        ];

        foreach ($users as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                array_merge($userData, [
                    'password' => Hash::make('password'),
                    'role' => 'user',
                    'is_active' => true,
                    'is_activated' => true,
                    'coins_balance' => 50,
                ])
            );
        }
    }
}
