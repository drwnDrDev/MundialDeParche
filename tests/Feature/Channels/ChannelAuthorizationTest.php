<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('active user can join presence-quinela', function () {
    $user = User::factory()->create(['is_active' => true]);

    $this->actingAs($user)
        ->postJson('/broadcasting/auth', [
            'channel_name' => 'presence-quinela',
            'socket_id'    => '123.456',
        ])
        ->assertSuccessful()
        ->assertJsonStructure(['auth', 'channel_data']);
});

it('inactive user cannot join presence-quinela', function () {
    $user = User::factory()->create(['is_active' => false]);

    $this->actingAs($user)
        ->postJson('/broadcasting/auth', [
            'channel_name' => 'presence-quinela',
            'socket_id'    => '123.456',
        ])
        ->assertForbidden();
});

it('user can authorize their own private channel', function () {
    $user = User::factory()->create(['is_active' => true]);

    $this->actingAs($user)
        ->postJson('/broadcasting/auth', [
            'channel_name' => 'private-user.' . $user->id,
            'socket_id'    => '123.456',
        ])
        ->assertSuccessful()
        ->assertJsonStructure(['auth']);
});

it('user cannot authorize another user private channel', function () {
    $user  = User::factory()->create(['is_active' => true]);
    $other = User::factory()->create(['is_active' => true]);

    $this->actingAs($user)
        ->postJson('/broadcasting/auth', [
            'channel_name' => 'private-user.' . $other->id,
            'socket_id'    => '123.456',
        ])
        ->assertForbidden();
});
