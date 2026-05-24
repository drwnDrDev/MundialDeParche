<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows admin dashboard with stats', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->withoutVite()->actingAs($admin)->get('/admin');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Dashboard')
        ->has('stats')
    );
});

it('blocks non-admins from the dashboard', function () {
    $user = User::factory()->create(['role' => 'user']);

    $this->withoutVite()->actingAs($user)->get('/admin')->assertStatus(403);
});
