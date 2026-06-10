<?php

use App\Events\ExactScoreAlert;
use App\Events\LiveScoreUpdated;
use App\Events\MatchScoreUpdated;
use App\Events\RankingUpdated;
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

function makeGroupFixtureWithScore(Round $round, int $home, int $away, int $matchNum = 1): Fixture
{
    $group   = Group::factory()->create();
    $homeTeam = Team::factory()->create(['group_id' => $group->id]);
    $awayTeam = Team::factory()->create(['group_id' => $group->id]);

    return Fixture::factory()->create([
        'round_id'     => $round->id,
        'group_id'     => $group->id,
        'home_team_id' => $homeTeam->id,
        'away_team_id' => $awayTeam->id,
        'home_score'   => $home,
        'away_score'   => $away,
        'status'       => 'finished',
        'match_number' => $matchNum,
    ]);
}

it('dispatches LiveScoreUpdated after match points calculation', function () {
    // Fake only the broadcast events (not MatchScoreUpdated itself)
    Event::fake([LiveScoreUpdated::class, RankingUpdated::class, ExactScoreAlert::class]);

    $round   = Round::factory()->f1()->create();
    $user    = User::factory()->create();
    $fixture = makeGroupFixtureWithScore($round, 2, 1);
    PredictionSubmission::factory()->submitted()->create(['user_id' => $user->id, 'round_id' => $round->id]);
    Prediction::factory()->create([
        'user_id' => $user->id, 'match_id' => $fixture->id,
        'predicted_home' => 1, 'predicted_away' => 0,
    ]);

    (new CalculateMatchPoints)->handle(new MatchScoreUpdated($fixture));

    Event::assertDispatched(LiveScoreUpdated::class, function ($e) use ($fixture) {
        return $e->matchId === $fixture->id
            && $e->homeScore === 2
            && $e->awayScore === 1
            && $e->isLive === false; // status = finished
    });
});

it('dispatches a single RankingUpdated with all affected users after calculation', function () {
    Event::fake([LiveScoreUpdated::class, RankingUpdated::class, ExactScoreAlert::class]);

    $round   = Round::factory()->f1()->create();
    $userA   = User::factory()->create();
    $userB   = User::factory()->create();
    $fixture = makeGroupFixtureWithScore($round, 2, 1);

    PredictionSubmission::factory()->submitted()->create(['user_id' => $userA->id, 'round_id' => $round->id]);
    PredictionSubmission::factory()->submitted()->create(['user_id' => $userB->id, 'round_id' => $round->id]);

    Prediction::factory()->create([
        'user_id' => $userA->id, 'match_id' => $fixture->id,
        'predicted_home' => 2, 'predicted_away' => 1, // exacto: 3 + 1 = 4 pts
    ]);
    Prediction::factory()->create([
        'user_id' => $userB->id, 'match_id' => $fixture->id,
        'predicted_home' => 1, 'predicted_away' => 0, // resultado: 1 pt
    ]);

    (new CalculateMatchPoints)->handle(new MatchScoreUpdated($fixture));

    Event::assertDispatchedTimes(RankingUpdated::class, 1);
    Event::assertDispatched(RankingUpdated::class, function ($e) use ($userA, $userB) {
        $byUser = collect($e->updates)->keyBy('user_id');

        return count($e->updates) === 2
            && $byUser[$userA->id]['total_points'] === 4
            && $byUser[$userA->id]['position'] === 1
            && $byUser[$userB->id]['total_points'] === 1
            && $byUser[$userB->id]['position'] === 2;
    });
});

it('dispatches ExactScoreAlert when a user gets pts_exact', function () {
    Event::fake([LiveScoreUpdated::class, RankingUpdated::class, ExactScoreAlert::class]);

    $round   = Round::factory()->f1()->create();
    $user    = User::factory()->create(['name' => 'Ana']);
    $fixture = makeGroupFixtureWithScore($round, 3, 1);

    PredictionSubmission::factory()->submitted()->create(['user_id' => $user->id, 'round_id' => $round->id]);
    Prediction::factory()->create([
        'user_id' => $user->id, 'match_id' => $fixture->id,
        'predicted_home' => 3, 'predicted_away' => 1, // exact match
    ]);

    (new CalculateMatchPoints)->handle(new MatchScoreUpdated($fixture));

    Event::assertDispatched(ExactScoreAlert::class, function ($e) use ($fixture) {
        return $e->userNames === ['Ana']
            && $e->matchId === $fixture->id
            && $e->homeScore === 3
            && $e->awayScore === 1;
    });
});

it('does not dispatch ExactScoreAlert when no user gets pts_exact', function () {
    Event::fake([LiveScoreUpdated::class, RankingUpdated::class, ExactScoreAlert::class]);

    $round   = Round::factory()->f1()->create();
    $user    = User::factory()->create();
    $fixture = makeGroupFixtureWithScore($round, 3, 1);

    PredictionSubmission::factory()->submitted()->create(['user_id' => $user->id, 'round_id' => $round->id]);
    Prediction::factory()->create([
        'user_id' => $user->id, 'match_id' => $fixture->id,
        'predicted_home' => 2, 'predicted_away' => 0, // correct result, not exact
    ]);

    (new CalculateMatchPoints)->handle(new MatchScoreUpdated($fixture));

    Event::assertNotDispatched(ExactScoreAlert::class);
});

it('dispatches a single ExactScoreAlert with every user who gets pts_exact', function () {
    Event::fake([LiveScoreUpdated::class, RankingUpdated::class, ExactScoreAlert::class]);

    $round   = Round::factory()->f1()->create();
    $userA   = User::factory()->create(['name' => 'Ana']);
    $userB   = User::factory()->create(['name' => 'Bob']);
    $fixture = makeGroupFixtureWithScore($round, 2, 0, matchNum: 1);

    PredictionSubmission::factory()->submitted()->create(['user_id' => $userA->id, 'round_id' => $round->id]);
    PredictionSubmission::factory()->submitted()->create(['user_id' => $userB->id, 'round_id' => $round->id]);

    Prediction::factory()->create([
        'user_id' => $userA->id, 'match_id' => $fixture->id,
        'predicted_home' => 2, 'predicted_away' => 0, // exact
    ]);
    Prediction::factory()->create([
        'user_id' => $userB->id, 'match_id' => $fixture->id,
        'predicted_home' => 2, 'predicted_away' => 0, // also exact
    ]);

    (new CalculateMatchPoints)->handle(new MatchScoreUpdated($fixture));

    Event::assertDispatchedTimes(ExactScoreAlert::class, 1);
    Event::assertDispatched(ExactScoreAlert::class, function ($e) {
        return collect($e->userNames)->sort()->values()->all() === ['Ana', 'Bob'];
    });
});

it('uses a constant number of queries regardless of how many users predicted', function () {
    Event::fake([LiveScoreUpdated::class, RankingUpdated::class, ExactScoreAlert::class]);

    $round   = Round::factory()->f1()->create();
    $fixture = makeGroupFixtureWithScore($round, 2, 1);

    $users = User::factory(8)->create();
    foreach ($users as $i => $user) {
        PredictionSubmission::factory()->submitted()->create(['user_id' => $user->id, 'round_id' => $round->id]);
        Prediction::factory()->create([
            'user_id' => $user->id, 'match_id' => $fixture->id,
            'predicted_home' => $i % 3, 'predicted_away' => ($i + 1) % 3,
        ]);
    }

    \Illuminate\Support\Facades\DB::enableQueryLog();
    (new CalculateMatchPoints)->handle(new MatchScoreUpdated($fixture));
    $queries = count(\Illuminate\Support\Facades\DB::getQueryLog());
    \Illuminate\Support\Facades\DB::disableQueryLog();

    // 8 usuarios con el código viejo costaban ~40+ queries; el batch debe ser constante
    expect($queries)->toBeLessThanOrEqual(15);
});

it('dispatches LiveScoreUpdated with isLive=true when match is in_progress', function () {
    Event::fake([LiveScoreUpdated::class, RankingUpdated::class, ExactScoreAlert::class]);

    $round   = Round::factory()->f1()->create();
    $group   = Group::factory()->create();
    $home    = Team::factory()->create(['group_id' => $group->id]);
    $away    = Team::factory()->create(['group_id' => $group->id]);
    $fixture = Fixture::factory()->create([
        'round_id'     => $round->id,
        'group_id'     => $group->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'home_score'   => 1,
        'away_score'   => 0,
        'status'       => 'in_progress',
        'match_number' => 1,
    ]);

    (new CalculateMatchPoints)->handle(new MatchScoreUpdated($fixture));

    Event::assertDispatched(LiveScoreUpdated::class, fn ($e) => $e->isLive === true);
});
