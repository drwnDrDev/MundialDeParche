<?php

use App\Models\Group;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
});

it('lists players with their teams', function () {
    $group  = Group::factory()->create(['name' => 'A']);
    $team   = Team::factory()->create(['group_id' => $group->id, 'name' => 'Argentina']);
    Player::factory()->create(['team_id' => $team->id, 'name' => 'Messi']);

    $response = $this->withoutVite()->actingAs($this->admin)->get('/admin/players');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Players/Index')
        ->has('players', 1)
        ->has('teams')
    );
});

it('creates a player', function () {
    $group = Group::factory()->create(['name' => 'A']);
    $team  = Team::factory()->create(['group_id' => $group->id]);

    $this->actingAs($this->admin)->post('/admin/players', [
        'team_id' => $team->id,
        'name'    => 'Lautaro Martínez',
    ])->assertRedirect();

    expect(Player::count())->toBe(1);
    expect(Player::first()->name)->toBe('Lautaro Martínez');
});

it('requires team_id and name to create a player', function () {
    $this->actingAs($this->admin)
        ->post('/admin/players', ['name' => '', 'team_id' => ''])
        ->assertSessionHasErrors(['name', 'team_id']);
});

it('updates a player', function () {
    $group  = Group::factory()->create(['name' => 'A']);
    $team   = Team::factory()->create(['group_id' => $group->id]);
    $player = Player::factory()->create(['team_id' => $team->id, 'name' => 'Old Name']);

    $this->actingAs($this->admin)->patch("/admin/players/{$player->id}", [
        'team_id' => $team->id,
        'name'    => 'Julián Álvarez',
    ])->assertRedirect();

    expect($player->fresh()->name)->toBe('Julián Álvarez');
});

it('deletes a player', function () {
    $group  = Group::factory()->create(['name' => 'A']);
    $team   = Team::factory()->create(['group_id' => $group->id]);
    $player = Player::factory()->create(['team_id' => $team->id]);

    $this->actingAs($this->admin)->delete("/admin/players/{$player->id}")
        ->assertRedirect();

    expect(Player::count())->toBe(0);
});
