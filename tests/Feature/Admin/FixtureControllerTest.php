<?php

use App\Events\ExactScoreAlert;
use App\Events\LiveScoreUpdated;
use App\Events\PointsUpdated;
use App\Models\Fixture;
use App\Models\Group;
use App\Models\Round;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    Event::fake([LiveScoreUpdated::class, PointsUpdated::class, ExactScoreAlert::class]);
});

it('lists fixtures filtered by round', function () {
    $round   = Round::factory()->f1()->create();
    $group   = Group::factory()->create(['name' => 'A']);
    $home    = Team::factory()->create(['group_id' => $group->id]);
    $away    = Team::factory()->create(['group_id' => $group->id]);
    Fixture::factory()->create([
        'round_id'     => $round->id,
        'group_id'     => $group->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
    ]);

    $response = $this->withoutVite()->actingAs($this->admin)
        ->get('/admin/fixtures?round_id=' . $round->id);

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Fixtures/Index')
        ->has('fixtures', 1)
        ->has('rounds')
        ->has('selectedRoundId')
    );
});

it('shows the create fixture form', function () {
    Round::factory()->f1()->create();

    $response = $this->withoutVite()->actingAs($this->admin)->get('/admin/fixtures/create');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Fixtures/Create')
        ->has('rounds')
        ->has('groups')
        ->has('teams')
    );
});

it('creates a group stage fixture', function () {
    $round = Round::factory()->f1()->create();
    $group = Group::factory()->create(['name' => 'A']);
    $home  = Team::factory()->create(['group_id' => $group->id]);
    $away  = Team::factory()->create(['group_id' => $group->id]);

    $this->actingAs($this->admin)->post('/admin/fixtures', [
        'round_id'     => $round->id,
        'group_id'     => $group->id,
        'match_number' => 1,
        'match_date'   => '2026-06-11 12:00:00',
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
    ])->assertRedirect();

    expect(Fixture::count())->toBe(1);
    expect(Fixture::first()->round_id)->toBe($round->id);
});

it('creates a knockout fixture with placeholders', function () {
    $round = Round::factory()->f2()->create();

    $this->actingAs($this->admin)->post('/admin/fixtures', [
        'round_id'         => $round->id,
        'match_number'     => 73,
        'match_date'       => '2026-06-29 16:00:00',
        'home_placeholder' => 'Ganador Grupo A',
        'away_placeholder' => 'Ganador Grupo B',
    ])->assertRedirect();

    $fixture = Fixture::first();
    expect($fixture->home_placeholder)->toBe('Ganador Grupo A');
    expect($fixture->home_team_id)->toBeNull();
});

it('requires round_id, match_number and match_date to create a fixture', function () {
    $this->actingAs($this->admin)->post('/admin/fixtures', [])
        ->assertSessionHasErrors(['round_id', 'match_number', 'match_date']);
});

it('shows the edit form for a fixture', function () {
    $round   = Round::factory()->f1()->create();
    $group   = Group::factory()->create(['name' => 'A']);
    $home    = Team::factory()->create(['group_id' => $group->id]);
    $away    = Team::factory()->create(['group_id' => $group->id]);
    $fixture = Fixture::factory()->create([
        'round_id'     => $round->id,
        'group_id'     => $group->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
    ]);

    $response = $this->withoutVite()->actingAs($this->admin)
        ->get("/admin/fixtures/{$fixture->id}/edit");

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Fixtures/Edit')
        ->has('fixture')
        ->has('teams')
    );
});

it('updates a fixture score and status', function () {
    $round   = Round::factory()->f1()->create();
    $group   = Group::factory()->create(['name' => 'A']);
    $home    = Team::factory()->create(['group_id' => $group->id]);
    $away    = Team::factory()->create(['group_id' => $group->id]);
    $fixture = Fixture::factory()->create([
        'round_id'     => $round->id,
        'group_id'     => $group->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'status'       => 'scheduled',
    ]);

    $this->actingAs($this->admin)->patch("/admin/fixtures/{$fixture->id}", [
        'round_id'           => $round->id,
        'group_id'           => $group->id,
        'match_number'       => $fixture->match_number,
        'match_date'         => $fixture->match_date->format('Y-m-d H:i:s'),
        'home_team_id'       => $home->id,
        'away_team_id'       => $away->id,
        'home_score'         => 2,
        'away_score'         => 1,
        'winner_team_id'     => $home->id,
        'went_to_extra_time' => false,
        'status'             => 'finished',
    ])->assertRedirect();

    $fresh = $fixture->fresh();
    expect($fresh->home_score)->toBe(2);
    expect($fresh->status)->toBe('finished');
});

it('assigns real teams to a knockout fixture', function () {
    $round   = Round::factory()->f2()->create();
    $groupA  = Group::factory()->create(['name' => 'A']);
    $groupB  = Group::factory()->create(['name' => 'B']);
    $teamA   = Team::factory()->create(['group_id' => $groupA->id]);
    $teamB   = Team::factory()->create(['group_id' => $groupB->id]);
    $fixture = Fixture::factory()->create([
        'round_id'         => $round->id,
        'group_id'         => null,
        'home_placeholder' => 'Ganador Grupo A',
        'away_placeholder' => 'Ganador Grupo B',
    ]);

    $this->actingAs($this->admin)->patch("/admin/fixtures/{$fixture->id}", [
        'round_id'           => $round->id,
        'match_number'       => $fixture->match_number,
        'match_date'         => $fixture->match_date->format('Y-m-d H:i:s'),
        'home_team_id'       => $teamA->id,
        'away_team_id'       => $teamB->id,
        'went_to_extra_time' => false,
        'status'             => 'scheduled',
    ])->assertRedirect();

    $fresh = $fixture->fresh();
    expect($fresh->home_team_id)->toBe($teamA->id);
    expect($fresh->away_team_id)->toBe($teamB->id);
});

it('deletes a fixture', function () {
    $round   = Round::factory()->f1()->create();
    $group   = Group::factory()->create(['name' => 'A']);
    $fixture = Fixture::factory()->create(['round_id' => $round->id, 'group_id' => $group->id]);

    $this->actingAs($this->admin)->delete("/admin/fixtures/{$fixture->id}")
        ->assertRedirect();

    expect(Fixture::count())->toBe(0);
});

it('dispatches LiveScoreUpdated when fixture updated to in_progress with scores', function () {
    $round   = Round::factory()->f1()->create(['is_open' => true]);
    $group   = Group::factory()->create(['name' => 'A']);
    $home    = Team::factory()->create(['group_id' => $group->id]);
    $away    = Team::factory()->create(['group_id' => $group->id]);
    $fixture = Fixture::factory()->create([
        'round_id'     => $round->id,
        'group_id'     => $group->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'status'       => 'scheduled',
        'match_number' => 1,
    ]);

    $this->actingAs($this->admin)
        ->patch("/admin/fixtures/{$fixture->id}", [
            'round_id'           => $round->id,
            'match_number'       => 1,
            'match_date'         => '2026-06-11 12:00:00',
            'home_team_id'       => $home->id,
            'away_team_id'       => $away->id,
            'home_score'         => 2,
            'away_score'         => 1,
            'status'             => 'in_progress',
            'went_to_extra_time' => false,
        ]);

    Event::assertDispatched(LiveScoreUpdated::class, fn ($e) =>
        $e->matchId === $fixture->id &&
        $e->homeScore === 2 &&
        $e->awayScore === 1
    );
});

it('does not dispatch LiveScoreUpdated when fixture status is scheduled', function () {
    $round   = Round::factory()->f1()->create(['is_open' => true]);
    $group   = Group::factory()->create(['name' => 'B']);
    $home    = Team::factory()->create(['group_id' => $group->id]);
    $away    = Team::factory()->create(['group_id' => $group->id]);
    $fixture = Fixture::factory()->create([
        'round_id'     => $round->id,
        'group_id'     => $group->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'match_number' => 2,
    ]);

    $this->actingAs($this->admin)
        ->patch("/admin/fixtures/{$fixture->id}", [
            'round_id'           => $round->id,
            'match_number'       => 2,
            'match_date'         => '2026-06-12 15:00:00',
            'home_team_id'       => $home->id,
            'away_team_id'       => $away->id,
            'status'             => 'scheduled',
            'went_to_extra_time' => false,
        ]);

    Event::assertNotDispatched(LiveScoreUpdated::class);
});
