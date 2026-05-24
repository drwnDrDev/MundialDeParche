<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('has required fields for the quinela', function () {
    $user = User::factory()->create([
        'role' => 'user',
        'is_active' => true,
        'is_activated' => false,
        'coins_balance' => 0,
        'total_points' => 0,
    ]);

    expect($user->role)->toBe('user')
        ->and($user->is_active)->toBeTrue()
        ->and($user->is_activated)->toBeFalse()
        ->and($user->coins_balance)->toBe(0)
        ->and($user->total_points)->toBe(0);
});

it('can be admin', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    expect($admin->isAdmin())->toBeTrue();
});

it('is not admin by default', function () {
    $user = User::factory()->create(['role' => 'user']);

    expect($user->isAdmin())->toBeFalse();
});
