<?php
// tests/Feature/ActivationTest.php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects unauthenticated users to login', function () {
    $this->get('/activation')->assertRedirect('/login');
});

it('redirects already-activated users to dashboard', function () {
    $user = User::factory()->activated()->create();

    $this->withoutVite()
        ->actingAs($user)
        ->get('/activation')
        ->assertRedirect(route('dashboard'));
});

it('renders Activation page for non-activated users', function () {
    $user = User::factory()->create(['is_activated' => false]);

    $this->withoutVite()
        ->actingAs($user)
        ->get('/activation')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Activation'));
});

it('passes admin contact props to Activation page', function () {
    config([
        'app.admin_name'      => 'Test Admin',
        'app.admin_phone'     => '+57 300 111 2222',
        'app.admin_whatsapp'  => '573001112222',
    ]);

    $user = User::factory()->create(['is_activated' => false]);

    $this->withoutVite()
        ->actingAs($user)
        ->get('/activation')
        ->assertInertia(fn ($page) => $page
            ->where('adminName', 'Test Admin')
            ->where('adminPhone', '+57 300 111 2222')
            ->where('adminWhatsApp', '573001112222')
        );
});
