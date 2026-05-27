<?php
// tests/Feature/WelcomeTest.php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders Welcome page for guests', function () {
    $this->withoutVite()
        ->get('/')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Welcome'));
});

it('redirects authenticated users from / to dashboard', function () {
    $user = User::factory()->activated()->create();

    $this->withoutVite()
        ->actingAs($user)
        ->get('/')
        ->assertRedirect(route('dashboard'));
});
