<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['is_active' => true, 'total_points' => 0]);
});

it('shows ranking page with active users ordered by total_points desc', function () {
    $first  = User::factory()->create(['is_active' => true, 'total_points' => 100]);
    $second = User::factory()->create(['is_active' => true, 'total_points' => 80]);
    $third  = User::factory()->create(['is_active' => true, 'total_points' => 60]);
    User::factory()->create(['is_active' => false, 'total_points' => 999]); // excluded

    $response = $this->withoutVite()->actingAs($this->user)->get('/ranking');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Ranking')
        ->has('users', 4) // 3 created + $this->user
    );

    $users = $response->original->getData()['page']['props']['users'];
    expect($users[0]['id'])->toBe($first->id);
    expect($users[0]['position'])->toBe(1);
    expect($users[1]['id'])->toBe($second->id);
    expect($users[1]['position'])->toBe(2);
    expect($users[2]['id'])->toBe($third->id);
    expect($users[2]['position'])->toBe(3);
});

it('includes id, name, total_points, position, avatarColor, delta in each row', function () {
    $response = $this->withoutVite()->actingAs($this->user)->get('/ranking');

    $response->assertInertia(fn ($page) => $page
        ->has('users.0.id')
        ->has('users.0.name')
        ->has('users.0.total_points')
        ->has('users.0.position')
        ->has('users.0.avatarColor')
        ->has('users.0.delta')
        ->has('pozo')
        ->has('pozo.total')
        ->has('pozo.players')
        ->has('pozo.prize1')
        ->has('pozo.prize2')
    );
});

it('guests cannot access ranking', function () {
    $this->get('/ranking')->assertRedirect('/login');
});
