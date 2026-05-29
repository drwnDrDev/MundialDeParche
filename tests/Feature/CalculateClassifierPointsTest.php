<?php

use App\Events\RoundFinalized;
use App\Listeners\CalculateClassifierPoints;
use App\Models\Fixture;
use App\Models\Group;
use App\Models\Prediction;
use App\Models\PredictionSubmission;
use App\Models\Round;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Helper: create a full 4-team group with 6 fixtures
function makeGroup(Round $round, string $name, int $matchStart): array
{
    $group = Group::factory()->create(['name' => $name]);
    $teams = Team::factory(4)->create(['group_id' => $group->id]);
    [$t1, $t2, $t3, $t4] = $teams;

    $pairs = [
        [$t1, $t2], [$t1, $t3], [$t1, $t4],
        [$t2, $t3], [$t2, $t4], [$t3, $t4],
    ];
    $fixtures = collect($pairs)->map(function ($pair, $i) use ($round, $group, $matchStart) {
        [$home, $away] = $pair;
        return Fixture::factory()->create([
            'round_id'     => $round->id,
            'group_id'     => $group->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'match_number' => $matchStart + $i,
        ]);
    });

    return ['group' => $group, 'teams' => $teams, 'fixtures' => $fixtures];
}

// Helper: set actual scores on fixtures
function setActualScores(iterable $fixtures, array $results): void
{
    foreach ($fixtures as $i => $fixture) {
        [$h, $a] = $results[$i];
        $fixture->update(['home_score' => $h, 'away_score' => $a, 'status' => 'finished']);
    }
}

// Helper: create user predictions for a set of fixtures
function createUserPredictions(User $user, iterable $fixtures, array $predictions): void
{
    foreach ($fixtures as $i => $fixture) {
        [$ph, $pa] = $predictions[$i];
        Prediction::factory()->create([
            'user_id'        => $user->id,
            'match_id'       => $fixture->id,
            'predicted_home' => $ph,
            'predicted_away' => $pa,
        ]);
    }
}

it('uses saved predicted_classifiers when available instead of re-deriving', function () {
    $round = Round::factory()->f1()->create(['is_open' => false, 'is_locked' => true]);

    // Create 8 groups, each with 2 teams and 1 fixture so thirds pool >= 8
    $teams = [];
    for ($i = 0; $i < 8; $i++) {
        $group = \App\Models\Group::factory()->create(['name' => chr(65 + $i)]); // A–H
        $home  = \App\Models\Team::factory()->create(['group_id' => $group->id]);
        $away  = \App\Models\Team::factory()->create(['group_id' => $group->id]);
        $teams[$i] = ['group' => $group, 'home' => $home, 'away' => $away];

        \App\Models\Fixture::factory()->create([
            'round_id'     => $round->id,
            'group_id'     => $group->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'home_score'   => 2,  // home wins in all groups
            'away_score'   => 0,
        ]);
    }

    $user = \App\Models\User::factory()->create(['is_activated' => true]);

    // Create Prediction rows where AWAY wins (opposite of real scores)
    // If re-derived, away teams would be classifiers; but we save home teams as classifiers
    foreach ($teams as $t) {
        $fixture = \App\Models\Fixture::where('round_id', $round->id)
            ->where('home_team_id', $t['home']->id)
            ->first();
        \App\Models\Prediction::factory()->create([
            'user_id'        => $user->id,
            'match_id'       => $fixture->id,
            'predicted_home' => 0,  // user's prediction: away wins
            'predicted_away' => 2,
        ]);
    }

    // Save predicted_classifiers with home teams (correct per real scores, but opposite of user's prediction scores)
    $savedClassifiers = collect($teams)->map(fn ($t, $i) => [
        'team_id'  => $t['home']->id,
        'group'    => chr(65 + $i),
        'position' => 1,
    ])->values()->all();

    $submission = \App\Models\PredictionSubmission::factory()->submitted()->create([
        'user_id'                => $user->id,
        'round_id'               => $round->id,
        'predicted_classifiers'  => $savedClassifiers,
    ]);

    event(new \App\Events\RoundFinalized($round));

    // Real classifiers = home teams (home_score 2 > 0).
    // Saved classifiers = home teams → all 8 match → 8 × points_classifier pts.
    // If re-derived from predictions (away wins), away teams would be classifiers → 0 correct.
    // The fact that pts > 0 proves the saved JSON path was taken, not re-derivation.
    $pts = 8 * $round->points_classifier;
    expect($submission->fresh()->pts_classifier)->toBe($pts);
});

it('awards classifier pts when user correctly predicts R1 top-2 classifiers', function () {
    $round = Round::factory()->f1()->create(['points_classifier' => 2]);
    $user  = User::factory()->create();
    PredictionSubmission::factory()->submitted()->create(['user_id' => $user->id, 'round_id' => $round->id]);

    ['fixtures' => $fixtures, 'teams' => $teams] = makeGroup($round, 'A', 1);
    [$t1, $t2, $t3, $t4] = $teams;

    // Real: T1=9pts, T2=6pts, T3=3pts, T4=0pts → classifiers: T1, T2
    setActualScores($fixtures, [
        [3,0],[3,0],[3,0], // T1 beats T2, T3, T4
        [1,0],[1,0],       // T2 beats T3, T4
        [1,0],             // T3 beats T4
    ]);

    // User predicts same outcomes → correctly predicts T1, T2 to classify
    createUserPredictions($user, $fixtures, [
        [3,0],[3,0],[3,0],
        [1,0],[1,0],
        [1,0],
    ]);

    app(CalculateClassifierPoints::class)->handle(new RoundFinalized($round));

    $submission = PredictionSubmission::where('user_id', $user->id)->where('round_id', $round->id)->first();
    expect($submission->pts_classifier)->toBe(4); // 2 correct classifiers (T1, T2) × 2 pts (no thirds: only 1 group < 8)
});

it('awards zero pts when user predicts wrong R1 classifiers', function () {
    $round = Round::factory()->f1()->create(['points_classifier' => 2]);
    $user  = User::factory()->create();
    PredictionSubmission::factory()->submitted()->create(['user_id' => $user->id, 'round_id' => $round->id]);

    ['fixtures' => $fixtures, 'teams' => $teams] = makeGroup($round, 'A', 1);
    [$t1, $t2, $t3, $t4] = $teams;

    // Real: T1, T2 classify
    setActualScores($fixtures, [
        [3,0],[3,0],[3,0],
        [1,0],[1,0],
        [1,0],
    ]);

    // User predicts T3, T4 win everything → wrong classifiers
    createUserPredictions($user, $fixtures, [
        [0,1],[0,1],[0,1],
        [0,1],[0,1],
        [1,0],
    ]);

    app(CalculateClassifierPoints::class)->handle(new RoundFinalized($round));

    $submission = PredictionSubmission::where('user_id', $user->id)->where('round_id', $round->id)->first();
    // Real classifiers: T1, T2 only (no thirds: only 1 group < 8)
    // User predicts T3, T4 win everything → derived classifiers = T3, T4 → intersection with {T1,T2} = 0 correct
    expect($submission->pts_classifier)->toBe(0);
});

it('does not award classifier pts to draft submissions', function () {
    $round = Round::factory()->f1()->create(['points_classifier' => 2]);
    $user  = User::factory()->create();
    PredictionSubmission::factory()->create(['user_id' => $user->id, 'round_id' => $round->id, 'status' => 'draft']);

    ['fixtures' => $fixtures] = makeGroup($round, 'A', 1);
    setActualScores($fixtures, [[3,0],[3,0],[3,0],[1,0],[1,0],[1,0]]);
    createUserPredictions($user, $fixtures, [[3,0],[3,0],[3,0],[1,0],[1,0],[1,0]]);

    app(CalculateClassifierPoints::class)->handle(new RoundFinalized($round));

    $submission = PredictionSubmission::where('user_id', $user->id)->where('round_id', $round->id)->first();
    expect($submission->pts_classifier)->toBe(0);
});

it('updates user total_points after R1 classifier calculation', function () {
    $round = Round::factory()->f1()->create(['points_classifier' => 2]);
    $user  = User::factory()->create(['total_points' => 0]);
    PredictionSubmission::factory()->submitted()->create(['user_id' => $user->id, 'round_id' => $round->id]);

    ['fixtures' => $fixtures] = makeGroup($round, 'A', 1);
    setActualScores($fixtures, [[3,0],[3,0],[3,0],[1,0],[1,0],[1,0]]);
    createUserPredictions($user, $fixtures, [[3,0],[3,0],[3,0],[1,0],[1,0],[1,0]]);

    app(CalculateClassifierPoints::class)->handle(new RoundFinalized($round));

    expect($user->fresh()->total_points)->toBe(4); // 2 correct classifiers (T1, T2) × 2 pts (no thirds: only 1 group < 8)
});

it('awards classifier pts for correctly predicted 8-best-thirds across 9 groups', function () {
    $round = Round::factory()->f1()->create(['points_classifier' => 2]);
    $user  = User::factory()->create();
    PredictionSubmission::factory()->submitted()->create(['user_id' => $user->id, 'round_id' => $round->id]);

    // Create 9 groups. In each group: T1 wins all, T2 beats T3+T4, T3 beats T4.
    // Real classifiers: T1 and T2 from each group (18 teams) + 8 best thirds out of 9.
    // T3 from each group has 3 pts, 1 gd, 1 gf (beats T4 1-0, loses to T1 and T2).
    // All 9 thirds are tied on pts/gd/gf, so top-8 thirds = any 8 of them.
    $allFixtures = collect();
    $matchStart = 1;

    for ($g = 0; $g < 9; $g++) {
        $name = chr(65 + $g); // A, B, C, ... I
        ['fixtures' => $fixtures, 'teams' => $teams] = makeGroup($round, $name, $matchStart);
        $matchStart += 6;

        // Real scores: T1 wins all, T2 beats T3+T4, T3 beats T4
        setActualScores($fixtures, [
            [2,0],[2,0],[2,0],
            [1,0],[1,0],
            [1,0],
        ]);

        // User predicts same outcomes
        createUserPredictions($user, $fixtures, [
            [2,0],[2,0],[2,0],
            [1,0],[1,0],
            [1,0],
        ]);

        $allFixtures = $allFixtures->concat($fixtures);
    }

    app(CalculateClassifierPoints::class)->handle(new RoundFinalized($round));

    $submission = PredictionSubmission::where('user_id', $user->id)->where('round_id', $round->id)->first();
    // 9 groups × 2 top classifiers = 18 correct + 8 out of 9 thirds correct = 26 correct × 2 = 52 pts
    expect($submission->pts_classifier)->toBe(52);
});

it('awards R2 classifier pts for correctly predicted R16 QF teams', function () {
    $round = Round::factory()->f2()->create(['points_classifier' => 4]);
    $user  = User::factory()->create();
    PredictionSubmission::factory()->submitted()->create(['user_id' => $user->id, 'round_id' => $round->id]);

    // R32: match_numbers 73–88 (real FIFA numbers), R16: 89–96
    $group = Group::factory()->create();
    $r32Teams = Team::factory(32)->create(['group_id' => $group->id]);

    for ($i = 0; $i < 16; $i++) {
        Fixture::factory()->create([
            'round_id'     => $round->id,
            'group_id'     => null,
            'home_team_id' => $r32Teams[$i * 2]->id,
            'away_team_id' => $r32Teams[$i * 2 + 1]->id,
            'match_number' => 73 + $i,
        ]);
    }

    // R16 fixtures (M89–M96): 8 matches, real winner_team_id set
    $r16Teams = Team::factory(16)->create(['group_id' => $group->id]);
    $r16Fixtures = collect();
    for ($i = 0; $i < 8; $i++) {
        $home = $r16Teams[$i * 2];
        $away = $r16Teams[$i * 2 + 1];
        $r16Fixtures->push(Fixture::factory()->create([
            'round_id'       => $round->id,
            'group_id'       => null,
            'home_team_id'   => $home->id,
            'away_team_id'   => $away->id,
            'winner_team_id' => $home->id, // home wins R16
            'match_number'   => 89 + $i,
        ]));
    }

    // User correctly predicts all 8 R16 home teams to win
    foreach ($r16Fixtures as $fixture) {
        Prediction::factory()->create([
            'user_id'        => $user->id,
            'match_id'       => $fixture->id,
            'predicted_home' => 2,
            'predicted_away' => 0, // home wins
        ]);
    }

    app(CalculateClassifierPoints::class)->handle(new RoundFinalized($round));

    $submission = PredictionSubmission::where('user_id', $user->id)->where('round_id', $round->id)->first();
    expect($submission->pts_classifier)->toBe(32); // 8 correct × 4 pts
});

it('awards partial R2 classifier pts when only some QF teams predicted correctly', function () {
    $round = Round::factory()->f2()->create(['points_classifier' => 4]);
    $user  = User::factory()->create();
    PredictionSubmission::factory()->submitted()->create(['user_id' => $user->id, 'round_id' => $round->id]);

    // R32: M73–M88, R16: M89–M96 (real FIFA match numbers)
    $group = Group::factory()->create();
    $r32Teams = Team::factory(32)->create(['group_id' => $group->id]);
    for ($i = 0; $i < 16; $i++) {
        Fixture::factory()->create([
            'round_id' => $round->id, 'group_id' => null,
            'home_team_id' => $r32Teams[$i * 2]->id,
            'away_team_id' => $r32Teams[$i * 2 + 1]->id,
            'match_number' => 73 + $i,
        ]);
    }

    $r16Teams = Team::factory(16)->create(['group_id' => $group->id]);
    $r16Fixtures = collect();
    for ($i = 0; $i < 8; $i++) {
        $home = $r16Teams[$i * 2];
        $away = $r16Teams[$i * 2 + 1];
        $r16Fixtures->push(Fixture::factory()->create([
            'round_id'       => $round->id, 'group_id' => null,
            'home_team_id'   => $home->id,
            'away_team_id'   => $away->id,
            'winner_team_id' => $home->id, // home always wins
            'match_number'   => 89 + $i,
        ]));
    }

    // User correctly predicts only first 3 home teams, predicts away for the rest
    foreach ($r16Fixtures as $i => $fixture) {
        Prediction::factory()->create([
            'user_id'  => $user->id,
            'match_id' => $fixture->id,
            'predicted_home' => $i < 3 ? 2 : 0,
            'predicted_away' => $i < 3 ? 0 : 2,
        ]);
    }

    app(CalculateClassifierPoints::class)->handle(new RoundFinalized($round));

    $submission = PredictionSubmission::where('user_id', $user->id)->where('round_id', $round->id)->first();
    expect($submission->pts_classifier)->toBe(12); // 3 correct × 4 pts
});
