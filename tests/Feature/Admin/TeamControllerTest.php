<?php

use App\Models\Group;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
});

it('lists teams with their groups', function () {
    $group = Group::factory()->create(['name' => 'A']);
    Team::factory()->create(['group_id' => $group->id, 'name' => 'Argentina', 'fifa_code' => 'ARG']);

    $response = $this->withoutVite()->actingAs($this->admin)->get('/admin/teams');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Teams/Index')
        ->has('teams', 1)
    );
});

it('shows the edit form for a team', function () {
    $group = Group::factory()->create(['name' => 'A']);
    $team  = Team::factory()->create(['group_id' => $group->id]);

    $response = $this->withoutVite()->actingAs($this->admin)->get("/admin/teams/{$team->id}/edit");

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Teams/Edit')
        ->has('team')
        ->has('groups')
    );
});

it('updates a team', function () {
    $group = Group::factory()->create(['name' => 'A']);
    $team  = Team::factory()->create(['group_id' => $group->id, 'name' => 'Old Name']);

    $this->actingAs($this->admin)->patch("/admin/teams/{$team->id}", [
        'name'     => 'Argentina',
        'fifa_code' => 'ARG',
        'flag_url' => 'https://example.com/arg.svg',
        'group_id' => $group->id,
    ]);

    expect($team->fresh()->name)->toBe('Argentina');
});

it('requires name and fifa_code to update a team', function () {
    $group = Group::factory()->create(['name' => 'A']);
    $team  = Team::factory()->create(['group_id' => $group->id]);

    $this->actingAs($this->admin)
        ->patch("/admin/teams/{$team->id}", ['name' => '', 'fifa_code' => ''])
        ->assertSessionHasErrors(['name', 'fifa_code']);
});
