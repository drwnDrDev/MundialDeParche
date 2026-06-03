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
                'name'          => 'Admin',
                'password'      => Hash::make('password'),
                'role'          => 'admin',
                'is_active'     => true,
                'is_activated'  => true,
                'coins_balance' => 50,
            ]
        );
    }
}
