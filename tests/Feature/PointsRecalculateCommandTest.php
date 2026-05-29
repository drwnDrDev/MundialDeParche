<?php

use App\Events\MatchScoreUpdated;
use App\Events\RoundFinalized;
use App\Models\Fixture;
use App\Models\Group;
use App\Models\Round;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('dispatches MatchScoreUpdated for a specific match with --match option', function () {
    Event::fake();
    $round   = Round::factory()->f1()->create();
    $group   = Group::factory()->create();
    $home    = Team::factory()->create(['group_id' => $group->id]);
    $away    = Team::factory()->create(['group_id' => $group->id]);
    $fixture = Fixture::factory()->create([
        'round_id' => $round->id, 'group_id' => $group->id,
        'home_team_id' => $home->id, 'away_team_id' => $away->id,
        'home_score' => 2, 'away_score' => 1, 'status' => 'finished',
        'match_number' => 1,
    ]);

    $this->artisan('points:recalculate', ['--match' => $fixture->id])
        ->assertSuccessful();

    Event::assertDispatched(MatchScoreUpdated::class, fn ($e) => $e->fixture->id === $fixture->id);
});

it('does not dispatch MatchScoreUpdated for match without score', function () {
    Event::fake();
    $round   = Round::factory()->f1()->create();
    $group   = Group::factory()->create();
    $fixture = Fixture::factory()->create([
        'round_id' => $round->id, 'group_id' => $group->id,
        'home_score' => null, 'away_score' => null, 'match_number' => 1,
    ]);

    $this->artisan('points:recalculate', ['--match' => $fixture->id])
        ->assertSuccessful();

    Event::assertNotDispatched(MatchScoreUpdated::class);
});

it('dispatches MatchScoreUpdated for every scored fixture in round with --round option', function () {
    Event::fake();
    $round = Round::factory()->f1()->create(['is_locked' => true]);
    $group = Group::factory()->create();
    $home  = Team::factory()->create(['group_id' => $group->id]);
    $away  = Team::factory()->create(['group_id' => $group->id]);

    $scored   = Fixture::factory()->create([
        'round_id' => $round->id, 'group_id' => $group->id,
        'home_team_id' => $home->id, 'away_team_id' => $away->id,
        'home_score' => 1, 'away_score' => 0, 'status' => 'finished',
        'match_number' => 1,
    ]);
    $unscored = Fixture::factory()->create([
        'round_id' => $round->id, 'group_id' => $group->id,
        'home_score' => null, 'away_score' => null, 'match_number' => 2,
    ]);

    $this->artisan('points:recalculate', ['--round' => $round->id])
        ->assertSuccessful();

    Event::assertDispatched(MatchScoreUpdated::class, fn ($e) => $e->fixture->id === $scored->id);
    Event::assertDispatched(RoundFinalized::class, fn ($e) => $e->round->id === $round->id);
});

it('does not dispatch RoundFinalized when round is not locked', function () {
    Event::fake();
    $round = Round::factory()->f1()->create(['is_locked' => false]);
    $group = Group::factory()->create();
    $fixture = Fixture::factory()->create([
        'round_id' => $round->id, 'group_id' => $group->id,
        'home_score' => 1, 'away_score' => 0, 'match_number' => 1,
    ]);

    $this->artisan('points:recalculate', ['--round' => $round->id])
        ->assertSuccessful();

    Event::assertNotDispatched(RoundFinalized::class);
});
