<?php

use App\Events\ExactScoreAlert;
use App\Events\LiveScoreUpdated;
use App\Events\MatchScoreUpdated;
use App\Events\PointsUpdated;
use App\Listeners\CalculateMatchPoints;
use App\Models\Fixture;
use App\Models\Group;
use App\Models\Prediction;
use App\Models\PredictionSubmission;
use App\Models\Round;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    Event::fake([LiveScoreUpdated::class, PointsUpdated::class, ExactScoreAlert::class]);
});

it('prediction_submission has pts_classifier column', function () {
    $sub = PredictionSubmission::factory()->submitted()->create(['pts_classifier' => 6]);
    expect($sub->fresh()->pts_classifier)->toBe(6);
});

// Helper: create a group-stage fixture with score
function groupFixtureWithScore(Round $round, int $homeScore, int $awayScore): array
{
    $group = Group::factory()->create();
    $home  = Team::factory()->create(['group_id' => $group->id]);
    $away  = Team::factory()->create(['group_id' => $group->id]);
    $fixture = Fixture::factory()->create([
        'round_id'     => $round->id,
        'group_id'     => $group->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'home_score'   => $homeScore,
        'away_score'   => $awayScore,
        'status'       => 'finished',
        'match_number' => rand(100, 60000),
    ]);
    return ['fixture' => $fixture, 'home' => $home, 'away' => $away];
}

it('awards pts_exact and pts_result for exact group stage score', function () {
    $round = Round::factory()->r1()->create();
    $user  = User::factory()->create();
    ['fixture' => $fixture] = groupFixtureWithScore($round, 2, 1);

    PredictionSubmission::factory()->submitted()->create(['user_id' => $user->id, 'round_id' => $round->id]);
    $prediction = Prediction::factory()->create([
        'user_id' => $user->id, 'match_id' => $fixture->id,
        'predicted_home' => 2, 'predicted_away' => 1,
    ]);

    (new CalculateMatchPoints)->handle(new MatchScoreUpdated($fixture));

    $prediction->refresh();
    expect($prediction->pts_exact)->toBe($round->points_exact);
    expect($prediction->pts_result)->toBe($round->points_result);
    expect($prediction->total_points)->toBe($round->points_exact + $round->points_result);
    expect($prediction->calculated_at)->not->toBeNull();
});

it('awards only pts_result for correct group stage result (not exact)', function () {
    $round = Round::factory()->r1()->create();
    $user  = User::factory()->create();
    ['fixture' => $fixture] = groupFixtureWithScore($round, 2, 1);

    PredictionSubmission::factory()->submitted()->create(['user_id' => $user->id, 'round_id' => $round->id]);
    $prediction = Prediction::factory()->create([
        'user_id' => $user->id, 'match_id' => $fixture->id,
        'predicted_home' => 3, 'predicted_away' => 0, // home wins, different score
    ]);

    (new CalculateMatchPoints)->handle(new MatchScoreUpdated($fixture));

    $prediction->refresh();
    expect($prediction->pts_exact)->toBe(0);
    expect($prediction->pts_result)->toBe($round->points_result);
});

it('awards pts_result for a group stage draw', function () {
    $round = Round::factory()->r1()->create();
    $user  = User::factory()->create();
    ['fixture' => $fixture] = groupFixtureWithScore($round, 1, 1);

    PredictionSubmission::factory()->submitted()->create(['user_id' => $user->id, 'round_id' => $round->id]);
    $prediction = Prediction::factory()->create([
        'user_id' => $user->id, 'match_id' => $fixture->id,
        'predicted_home' => 2, 'predicted_away' => 2, // draw, different score
    ]);

    (new CalculateMatchPoints)->handle(new MatchScoreUpdated($fixture));

    $prediction->refresh();
    expect($prediction->pts_exact)->toBe(0);
    expect($prediction->pts_result)->toBe($round->points_result);
});

it('awards zero points for wrong group stage prediction', function () {
    $round = Round::factory()->r1()->create();
    $user  = User::factory()->create();
    ['fixture' => $fixture] = groupFixtureWithScore($round, 2, 0);

    PredictionSubmission::factory()->submitted()->create(['user_id' => $user->id, 'round_id' => $round->id]);
    $prediction = Prediction::factory()->create([
        'user_id' => $user->id, 'match_id' => $fixture->id,
        'predicted_home' => 0, 'predicted_away' => 2, // wrong winner
    ]);

    (new CalculateMatchPoints)->handle(new MatchScoreUpdated($fixture));

    $prediction->refresh();
    expect($prediction->pts_exact)->toBe(0);
    expect($prediction->pts_result)->toBe(0);
    expect($prediction->total_points)->toBe(0);
});

it('awards pts_result for correct knockout winner (via winner_team_id)', function () {
    $round = Round::factory()->r2()->create();
    $group = Group::factory()->create();
    $home  = Team::factory()->create(['group_id' => $group->id]);
    $away  = Team::factory()->create(['group_id' => $group->id]);
    $fixture = Fixture::factory()->create([
        'round_id'       => $round->id,
        'group_id'       => null,
        'home_team_id'   => $home->id,
        'away_team_id'   => $away->id,
        'home_score'     => 1,
        'away_score'     => 1,
        'winner_team_id' => $home->id, // won by ET/penalties
        'status'         => 'finished',
        'match_number'   => rand(100, 60000),
    ]);
    $user = User::factory()->create();
    PredictionSubmission::factory()->submitted()->create(['user_id' => $user->id, 'round_id' => $round->id]);
    $prediction = Prediction::factory()->create([
        'user_id' => $user->id, 'match_id' => $fixture->id,
        'predicted_home' => 1, 'predicted_away' => 0, // user picked home to win
    ]);

    (new CalculateMatchPoints)->handle(new MatchScoreUpdated($fixture));

    $prediction->refresh();
    expect($prediction->pts_exact)->toBe(0); // predicted 1-0, actual 90-min was 1-1
    expect($prediction->pts_result)->toBe($round->points_result); // home won ✓
});

it('awards pts_exact for exact 90-min score in knockout, even if winner differs via ET', function () {
    $round = Round::factory()->r2()->create();
    $group = Group::factory()->create();
    $home  = Team::factory()->create(['group_id' => $group->id]);
    $away  = Team::factory()->create(['group_id' => $group->id]);
    $fixture = Fixture::factory()->create([
        'round_id'       => $round->id,
        'group_id'       => null,
        'home_team_id'   => $home->id,
        'away_team_id'   => $away->id,
        'home_score'     => 1,
        'away_score'     => 1,
        'winner_team_id' => $home->id, // won by ET/penalties
        'went_to_extra_time' => true,
        'status'         => 'finished',
        'match_number'   => rand(100, 60000),
    ]);
    $user = User::factory()->create();
    PredictionSubmission::factory()->submitted()->create(['user_id' => $user->id, 'round_id' => $round->id]);
    $prediction = Prediction::factory()->create([
        'user_id' => $user->id, 'match_id' => $fixture->id,
        'predicted_home' => 1, 'predicted_away' => 1, // predicted 1-1 (exact 90-min score)
    ]);

    (new CalculateMatchPoints)->handle(new MatchScoreUpdated($fixture));

    $prediction->refresh();
    expect($prediction->pts_exact)->toBe($round->points_exact); // exact 90-min ✓
    expect($prediction->pts_result)->toBe(0); // no winner predicted from 1-1 in knockout
});

it('does not calculate points for draft predictions', function () {
    $round = Round::factory()->r1()->create();
    $user  = User::factory()->create();
    ['fixture' => $fixture] = groupFixtureWithScore($round, 2, 1);

    PredictionSubmission::factory()->create(['user_id' => $user->id, 'round_id' => $round->id, 'status' => 'draft']);
    $prediction = Prediction::factory()->create([
        'user_id' => $user->id, 'match_id' => $fixture->id,
        'predicted_home' => 2, 'predicted_away' => 1,
    ]);

    (new CalculateMatchPoints)->handle(new MatchScoreUpdated($fixture));

    $prediction->refresh();
    expect($prediction->pts_exact)->toBe(0);
    expect($prediction->total_points)->toBe(0);
});

it('skips calculation when score is null', function () {
    $round = Round::factory()->r1()->create();
    $user  = User::factory()->create();
    $group = Group::factory()->create();
    $fixture = Fixture::factory()->create([
        'round_id' => $round->id, 'group_id' => $group->id,
        'home_score' => null, 'away_score' => null,
        'match_number' => rand(100, 60000),
    ]);
    PredictionSubmission::factory()->submitted()->create(['user_id' => $user->id, 'round_id' => $round->id]);
    $prediction = Prediction::factory()->create([
        'user_id' => $user->id, 'match_id' => $fixture->id,
        'predicted_home' => 1, 'predicted_away' => 0,
    ]);

    (new CalculateMatchPoints)->handle(new MatchScoreUpdated($fixture));

    $prediction->refresh();
    expect($prediction->pts_exact)->toBe(0);
});

it('updates user total_points after match calculation', function () {
    $round = Round::factory()->r1()->create(['points_exact' => 3, 'points_result' => 1]);
    $user  = User::factory()->create(['total_points' => 0]);
    ['fixture' => $fixture] = groupFixtureWithScore($round, 2, 1);

    PredictionSubmission::factory()->submitted()->create(['user_id' => $user->id, 'round_id' => $round->id]);
    Prediction::factory()->create([
        'user_id' => $user->id, 'match_id' => $fixture->id,
        'predicted_home' => 2, 'predicted_away' => 1, // exact
    ]);

    (new CalculateMatchPoints)->handle(new MatchScoreUpdated($fixture));

    expect($user->fresh()->total_points)->toBe(4); // 3 exact + 1 result
});

it('awards zero pts_result for predicted draw in knockout even if away team wins', function () {
    $round = Round::factory()->r2()->create();
    $group = Group::factory()->create();
    $home  = Team::factory()->create(['group_id' => $group->id]);
    $away  = Team::factory()->create(['group_id' => $group->id]);
    $fixture = Fixture::factory()->create([
        'round_id'       => $round->id,
        'group_id'       => null,
        'home_team_id'   => $home->id,
        'away_team_id'   => $away->id,
        'home_score'     => 1,
        'away_score'     => 1,
        'winner_team_id' => $away->id, // away wins via penalties
        'status'         => 'finished',
        'match_number'   => rand(100, 60000),
    ]);
    $user = User::factory()->create();
    PredictionSubmission::factory()->submitted()->create(['user_id' => $user->id, 'round_id' => $round->id]);
    $prediction = Prediction::factory()->create([
        'user_id'        => $user->id,
        'match_id'       => $fixture->id,
        'predicted_home' => 1,
        'predicted_away' => 1, // user predicted draw → no pts_result in knockout
    ]);

    (new CalculateMatchPoints)->handle(new MatchScoreUpdated($fixture));

    $prediction->refresh();
    expect($prediction->pts_result)->toBe(0);
});
