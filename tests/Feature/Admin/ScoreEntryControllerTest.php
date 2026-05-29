<?php

use App\Events\LiveScoreUpdated;
use App\Events\MatchScoreUpdated;
use App\Models\Fixture;
use App\Models\Round;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('renders the score entry page for admin', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Round::factory()->f1()->create(['is_open' => true]);

    $this->actingAs($admin)
        ->get(route('admin.score-entry'))
        ->assertOk()
        ->assertInertia(fn ($p) => $p->component('Admin/ScoreEntry'));
});

it('blocks non-admin from score entry', function () {
    $user = User::factory()->create(['role' => 'user']);

    $this->actingAs($user)
        ->get(route('admin.score-entry'))
        ->assertForbidden();
});

it('updates fixture score and dispatches MatchScoreUpdated', function () {
    Event::fake([MatchScoreUpdated::class]);

    $admin   = User::factory()->create(['role' => 'admin']);
    $round   = Round::factory()->f1()->create(['is_open' => true]);
    $home    = Team::factory()->create(['fifa_code' => 'COL']);
    $away    = Team::factory()->create(['fifa_code' => 'BRA']);
    $fixture = Fixture::factory()->create([
        'round_id'     => $round->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'status'       => 'in_progress',
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.score-entry.update', $fixture->id), [
            'home_score'     => 2,
            'away_score'     => 1,
            'winner_team_id' => $home->id,
            'status'         => 'finished',
        ])
        ->assertRedirect();

    expect($fixture->fresh()->home_score)->toBe(2);
    expect($fixture->fresh()->away_score)->toBe(1);
    expect($fixture->fresh()->winner_team_id)->toBe($home->id);

    Event::assertDispatched(MatchScoreUpdated::class);
});

it('rejects score update with invalid winner_team_id', function () {
    $admin   = User::factory()->create(['role' => 'admin']);
    $round   = Round::factory()->f1()->create(['is_open' => true]);
    $home    = Team::factory()->create(['fifa_code' => 'COL']);
    $away    = Team::factory()->create(['fifa_code' => 'BRA']);
    $other   = Team::factory()->create(['fifa_code' => 'ARG']);
    $fixture = Fixture::factory()->create([
        'round_id'     => $round->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.score-entry.update', $fixture->id), [
            'home_score'     => 2,
            'away_score'     => 1,
            'winner_team_id' => $other->id,
            'status'         => 'finished',
        ])
        ->assertSessionHasErrors('winner_team_id');
});

it('blocks updating a finished fixture via score entry', function () {
    $admin   = User::factory()->create(['role' => 'admin']);
    $round   = Round::factory()->f1()->create(['is_open' => true]);
    $home    = Team::factory()->create(['fifa_code' => 'ARG']);
    $away    = Team::factory()->create(['fifa_code' => 'CHI']);
    $fixture = Fixture::factory()->create([
        'round_id'     => $round->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'home_score'   => 2,
        'away_score'   => 1,
        'status'       => 'finished',
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.score-entry.update', $fixture->id), [
            'home_score' => 3,
            'away_score' => 0,
            'status'     => 'finished',
        ])
        ->assertRedirect();

    expect($fixture->fresh()->home_score)->toBe(2);
});

it('dispatches LiveScoreUpdated when fixture is set to in_progress', function () {
    Event::fake([LiveScoreUpdated::class, MatchScoreUpdated::class]);

    $admin   = User::factory()->create(['role' => 'admin']);
    $round   = Round::factory()->f1()->create(['is_open' => true]);
    $home    = Team::factory()->create(['fifa_code' => 'ECU']);
    $away    = Team::factory()->create(['fifa_code' => 'URU']);
    $fixture = Fixture::factory()->create([
        'round_id'     => $round->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'status'       => 'scheduled',
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.score-entry.update', $fixture->id), [
            'home_score' => 1,
            'away_score' => 0,
            'status'     => 'in_progress',
        ]);

    Event::assertDispatched(LiveScoreUpdated::class, function ($e) use ($fixture) {
        return $e->matchId === $fixture->id && $e->isLive === true;
    });
});
