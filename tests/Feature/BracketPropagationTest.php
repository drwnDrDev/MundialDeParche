<?php

use App\Events\MatchScoreUpdated;
use App\Models\Fixture;
use App\Models\Round;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('fills home slot of next match when winner is set', function () {
    $brazil = Team::factory()->create(['name' => 'Brazil', 'fifa_code' => 'BRA']);
    $france = Team::factory()->create(['name' => 'France', 'fifa_code' => 'FRA']);

    $f2 = Round::factory()->f2()->create();
    $f3 = Round::factory()->f3()->create();

    $source = Fixture::factory()->create([
        'round_id'     => $f2->id,
        'match_number' => 73,
        'home_team_id' => $brazil->id,
        'away_team_id' => $france->id,
        'home_score'   => 2,
        'away_score'   => 1,
        'winner_team_id' => $brazil->id,
        'status'       => 'finished',
    ]);

    $target = Fixture::factory()->create([
        'round_id'     => $f3->id,
        'match_number' => 89,
        'home_team_id' => null,
        'away_team_id' => null,
    ]);

    $source->update([
        'winner_feeds_match_id' => $target->id,
        'winner_feeds_slot'     => 'home',
    ]);

    MatchScoreUpdated::dispatch($source->fresh());

    expect($target->fresh()->home_team_id)->toBe($brazil->id);
    expect($target->fresh()->away_team_id)->toBeNull();
});

it('fills away slot of next match when winner is set', function () {
    $argentina = Team::factory()->create(['name' => 'Argentina', 'fifa_code' => 'ARG']);
    $spain     = Team::factory()->create(['name' => 'Spain',     'fifa_code' => 'ESP']);

    $f2 = Round::factory()->f2()->create();
    $f3 = Round::factory()->f3()->create();

    $source = Fixture::factory()->create([
        'round_id'     => $f2->id,
        'match_number' => 75,
        'home_team_id' => $argentina->id,
        'away_team_id' => $spain->id,
        'home_score'   => 0,
        'away_score'   => 1,
        'winner_team_id' => $spain->id,
        'status'       => 'finished',
    ]);

    $target = Fixture::factory()->create([
        'round_id'     => $f3->id,
        'match_number' => 89,
        'home_team_id' => null,
        'away_team_id' => null,
    ]);

    $source->update([
        'winner_feeds_match_id' => $target->id,
        'winner_feeds_slot'     => 'away',
    ]);

    MatchScoreUpdated::dispatch($source->fresh());

    expect($target->fresh()->away_team_id)->toBe($spain->id);
    expect($target->fresh()->home_team_id)->toBeNull();
});

it('does nothing when winner_feeds_match_id is null', function () {
    $brazil = Team::factory()->create(['fifa_code' => 'BRA']);
    $france = Team::factory()->create(['fifa_code' => 'FRA']);

    $f2 = Round::factory()->f2()->create();

    $source = Fixture::factory()->create([
        'round_id'              => $f2->id,
        'match_number'          => 73,
        'home_team_id'          => $brazil->id,
        'away_team_id'          => $france->id,
        'home_score'            => 2,
        'away_score'            => 1,
        'winner_team_id'        => $brazil->id,
        'winner_feeds_match_id' => null,
        'winner_feeds_slot'     => null,
        'status'                => 'finished',
    ]);

    MatchScoreUpdated::dispatch($source->fresh());

    expect(true)->toBeTrue();
});

it('does nothing when winner_team_id is null', function () {
    $brazil = Team::factory()->create(['fifa_code' => 'BRA']);
    $france = Team::factory()->create(['fifa_code' => 'FRA']);
    $f2     = Round::factory()->f2()->create();
    $f3     = Round::factory()->f3()->create();

    $source = Fixture::factory()->create([
        'round_id'       => $f2->id,
        'match_number'   => 73,
        'home_team_id'   => $brazil->id,
        'away_team_id'   => $france->id,
        'home_score'     => 2,
        'away_score'     => 1,
        'winner_team_id' => null,
        'status'         => 'in_progress',
    ]);

    $target = Fixture::factory()->create([
        'round_id'     => $f3->id,
        'match_number' => 89,
        'home_team_id' => null,
        'away_team_id' => null,
    ]);

    $source->update([
        'winner_feeds_match_id' => $target->id,
        'winner_feeds_slot'     => 'home',
    ]);

    MatchScoreUpdated::dispatch($source->fresh());

    expect($target->fresh()->home_team_id)->toBeNull();
});
