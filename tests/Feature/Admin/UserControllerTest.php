<?php

use App\Models\PredictionSubmission;
use App\Models\Round;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
});

it('lists non-admin users', function () {
    User::factory()->create(['role' => 'user', 'name' => 'Regular User']);

    $response = $this->withoutVite()->actingAs($this->admin)->get('/admin/users');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Users/Index')
        ->has('users', 1)
        ->has('rounds')
    );
});

it('creates a new user', function () {
    $this->actingAs($this->admin)->post('/admin/users', [
        'name'                  => 'Juan Pérez',
        'email'                 => 'juan@example.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
    ])->assertRedirect();

    $user = User::where('email', 'juan@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->role)->toBe('user');
    expect($user->is_active)->toBeTrue();
});

it('requires name, unique email and confirmed password to create user', function () {
    User::factory()->create(['email' => 'existing@example.com']);

    $this->actingAs($this->admin)
        ->post('/admin/users', [
            'name'     => '',
            'email'    => 'existing@example.com',
            'password' => 'short',
        ])
        ->assertSessionHasErrors(['name', 'email', 'password']);
});

it('toggles user is_active status', function () {
    $user = User::factory()->create(['role' => 'user', 'is_active' => true]);

    $this->actingAs($this->admin)->post("/admin/users/{$user->id}/toggle-active")
        ->assertRedirect();

    expect($user->fresh()->is_active)->toBeFalse();

    $this->actingAs($this->admin)->post("/admin/users/{$user->id}/toggle-active")
        ->assertRedirect();

    expect($user->fresh()->is_active)->toBeTrue();
});

it('activates a user in the pot and records coin transaction', function () {
    $user = User::factory()->create(['role' => 'user', 'is_activated' => false, 'coins_balance' => 0]);

    $this->actingAs($this->admin)->post("/admin/users/{$user->id}/activate-pot")
        ->assertRedirect();

    expect($user->fresh()->is_activated)->toBeTrue();
    expect($user->fresh()->coins_balance)->toBe(50);
    expect($user->coinTransactions()->where('type', 'credit')->where('amount', 50)->exists())->toBeTrue();
});

it('does not double-activate a user in the pot', function () {
    $user = User::factory()->create(['role' => 'user', 'is_activated' => true, 'coins_balance' => 50]);

    $this->actingAs($this->admin)->post("/admin/users/{$user->id}/activate-pot")
        ->assertRedirect();

    expect($user->fresh()->coins_balance)->toBe(50);
    expect($user->coinTransactions()->count())->toBe(0);
});

it('deactivates a user from the pot and records coin transaction', function () {
    $user = User::factory()->create(['role' => 'user', 'is_activated' => true, 'coins_balance' => 50]);

    $this->actingAs($this->admin)->post("/admin/users/{$user->id}/deactivate-pot")
        ->assertRedirect();

    expect($user->fresh()->is_activated)->toBeFalse();
    expect($user->fresh()->coins_balance)->toBe(0);
    expect($user->coinTransactions()->where('type', 'debit')->where('amount', 50)->exists())->toBeTrue();
});

it('reopens predictions for a user and round', function () {
    $user  = User::factory()->create(['role' => 'user']);
    $round = Round::factory()->r1()->create();

    PredictionSubmission::create([
        'user_id'      => $user->id,
        'round_id'     => $round->id,
        'status'       => 'locked',
        'submitted_at' => now(),
    ]);

    $this->actingAs($this->admin)->post("/admin/users/{$user->id}/reopen-predictions", [
        'round_id' => $round->id,
    ])->assertRedirect();

    $submission = PredictionSubmission::where('user_id', $user->id)->where('round_id', $round->id)->first();
    expect($submission->status)->toBe('draft');
    expect($submission->submitted_at)->toBeNull();
});

it('requires round_id to reopen predictions', function () {
    $user = User::factory()->create(['role' => 'user']);

    $this->actingAs($this->admin)
        ->post("/admin/users/{$user->id}/reopen-predictions", [])
        ->assertSessionHasErrors(['round_id']);
});

it('blocks unauthenticated access to admin user routes', function () {
    $user = User::factory()->create(['role' => 'user']);

    $this->post('/admin/users')->assertRedirect('/login');
    $this->post("/admin/users/{$user->id}/toggle-active")->assertRedirect('/login');
    $this->post("/admin/users/{$user->id}/activate-pot")->assertRedirect('/login');
});
