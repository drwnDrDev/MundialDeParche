<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('blocks non-admin users from admin routes', function () {
    $user = User::factory()->create(['role' => 'user']);

    $response = $this->withoutVite()->actingAs($user)->get('/admin');

    $response->assertStatus(403);
});

it('blocks inactive admin users from admin routes', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => false]);

    $response = $this->withoutVite()->actingAs($admin)->get('/admin');

    $response->assertStatus(403);
});

it('allows admin users to access admin routes', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->withoutVite()->actingAs($admin)->get('/admin');

    $response->assertStatus(200);
});

it('redirects guests to login on admin routes', function () {
    $response = $this->get('/admin');

    $response->assertRedirect('/login');
});
