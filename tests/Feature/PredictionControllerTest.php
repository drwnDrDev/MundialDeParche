<?php

use App\Models\Round;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'user', 'is_active' => true]);
});

it('lists rounds with user submission status', function () {
    $open   = Round::factory()->r1()->create(['is_open' => true]);
    $closed = Round::factory()->r2()->create(['is_open' => false, 'order' => 2]);

    $response = $this->withoutVite()->actingAs($this->user)->get('/predictions');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Predictions/Index')
        ->has('rounds', 2)
        ->has('submissions')
    );
});

it('blocks guests from predictions index', function () {
    $this->get('/predictions')->assertRedirect('/login');
});

it('shows a round prediction page when round is open with teams assigned', function () {
    $round   = Round::factory()->r1()->create(['is_open' => true]);
    $group   = \App\Models\Group::factory()->create(['name' => 'A']);
    $home    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $away    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    \App\Models\Fixture::factory()->create([
        'round_id'     => $round->id,
        'group_id'     => $group->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
    ]);

    $response = $this->withoutVite()->actingAs($this->user)->get("/predictions/{$round->id}");

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Predictions/Round')
        ->has('round')
        ->has('fixtures', 1)
        ->has('predictions')
        ->has('submission')
    );
});

it('redirects from round show when fixtures have unassigned teams', function () {
    $round = Round::factory()->r2()->create(['is_open' => true]);
    \App\Models\Fixture::factory()->create([
        'round_id'     => $round->id,
        'home_team_id' => null,
        'away_team_id' => null,
    ]);

    $this->actingAs($this->user)->get("/predictions/{$round->id}")
        ->assertRedirect(route('predictions.index'));
});

it('shows round even when closed (read-only)', function () {
    $round   = Round::factory()->r1()->create(['is_open' => false, 'is_locked' => true]);
    $group   = \App\Models\Group::factory()->create(['name' => 'A']);
    $home    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $away    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    \App\Models\Fixture::factory()->create([
        'round_id'     => $round->id,
        'group_id'     => $group->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
    ]);

    $response = $this->withoutVite()->actingAs($this->user)->get("/predictions/{$round->id}");

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Predictions/Round'));
});
