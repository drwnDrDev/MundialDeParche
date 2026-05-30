<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SimulationSeeder extends Seeder
{
    /**
     * Siembra usuarios de prueba con credenciales conocidas para
     * la simulación Layer 2 (sub-agentes HTTP).
     */
    public function run(): void
    {
        // Admin de simulación
        User::firstOrCreate(
            ['email' => 'admin@sim.test'],
            [
                'name'      => 'Admin Sim',
                'password'  => Hash::make('simpassword'),
                'role'      => 'admin',
                'is_active' => true,
            ]
        );

        // Jugadores de simulación
        $players = [
            ['name' => 'Alice Sim',   'email' => 'alice@sim.test'],
            ['name' => 'Bob Sim',     'email' => 'bob@sim.test'],
            ['name' => 'Carlos Sim',  'email' => 'carlos@sim.test'],
            ['name' => 'Diana Sim',   'email' => 'diana@sim.test'],
            ['name' => 'Ernesto Sim', 'email' => 'ernesto@sim.test'],
        ];

        foreach ($players as $p) {
            User::firstOrCreate(
                ['email' => $p['email']],
                [
                    'name'          => $p['name'],
                    'password'      => Hash::make('simpassword'),
                    'role'          => 'user',
                    'is_active'     => true,
                    'is_activated'  => true,
                    'coins_balance' => 0,
                ]
            );
        }
    }
}
