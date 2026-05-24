<?php

use App\Events\MatchScoreUpdated;
use App\Models\Fixture;
use App\Models\Group;
use App\Models\Round;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
});

it('dispatches MatchScoreUpdated when fixture is updated with a score', function () {
    Event::fake();
    $round   = Round::factory()->r1()->create();
    $group   = Group::factory()->create();
    $home    = Team::factory()->create(['group_id' => $group->id]);
    $away    = Team::factory()->create(['group_id' => $group->id]);
    $fixture = Fixture::factory()->create([
        'round_id'     => $round->id,
        'group_id'     => $group->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'match_number' => 1,
    ]);

    $this->actingAs($this->admin)->patch("/admin/fixtures/{$fixture->id}", [
        'round_id'           => $round->id,
        'group_id'           => $group->id,
        'match_number'       => 1,
        'match_date'         => '2026-06-15 18:00:00',
        'home_team_id'       => $home->id,
        'away_team_id'       => $away->id,
        'home_placeholder'   => null,
        'away_placeholder'   => null,
        'home_score'         => 2,
        'away_score'         => 1,
        'winner_team_id'     => null,
        'went_to_extra_time' => false,
        'status'             => 'finished',
    ]);

    Event::assertDispatched(MatchScoreUpdated::class, function ($event) use ($fixture) {
        return $event->fixture->id === $fixture->id;
    });
});

it('does not dispatch MatchScoreUpdated when score is still null', function () {
    Event::fake();
    $round   = Round::factory()->r1()->create();
    $group   = Group::factory()->create();
    $fixture = Fixture::factory()->create([
        'round_id' => $round->id, 'group_id' => $group->id, 'match_number' => 1,
    ]);

    $this->actingAs($this->admin)->patch("/admin/fixtures/{$fixture->id}", [
        'round_id'           => $round->id,
        'group_id'           => $group->id,
        'match_number'       => 1,
        'match_date'         => '2026-06-15 18:00:00',
        'home_team_id'       => null,
        'away_team_id'       => null,
        'home_placeholder'   => null,
        'away_placeholder'   => null,
        'home_score'         => null,
        'away_score'         => null,
        'winner_team_id'     => null,
        'went_to_extra_time' => false,
        'status'             => 'scheduled',
    ]);

    Event::assertNotDispatched(MatchScoreUpdated::class);
});

it('does not dispatch MatchScoreUpdated when score is cleared to null', function () {
    Event::fake();
    $round   = Round::factory()->r1()->create();
    $group   = Group::factory()->create();
    $home    = Team::factory()->create(['group_id' => $group->id]);
    $away    = Team::factory()->create(['group_id' => $group->id]);
    $fixture = Fixture::factory()->create([
        'round_id'     => $round->id,
        'group_id'     => $group->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'home_score'   => 2,
        'away_score'   => 1,
        'status'       => 'finished',
        'match_number' => 2,
    ]);

    $this->actingAs($this->admin)->patch("/admin/fixtures/{$fixture->id}", [
        'round_id'           => $round->id,
        'group_id'           => $group->id,
        'match_number'       => 2,
        'match_date'         => '2026-06-15 18:00:00',
        'home_team_id'       => $home->id,
        'away_team_id'       => $away->id,
        'home_placeholder'   => null,
        'away_placeholder'   => null,
        'home_score'         => null,
        'away_score'         => null,
        'winner_team_id'     => null,
        'went_to_extra_time' => false,
        'status'             => 'scheduled',
    ]);

    Event::assertNotDispatched(MatchScoreUpdated::class);
});
