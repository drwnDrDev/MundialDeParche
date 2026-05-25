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

it('awards classifier pts when user correctly predicts R1 top-2 classifiers', function () {
    $round = Round::factory()->r1()->create(['points_classifier' => 2]);
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

    (new CalculateClassifierPoints)->handle(new RoundFinalized($round));

    $submission = PredictionSubmission::where('user_id', $user->id)->where('round_id', $round->id)->first();
    expect($submission->pts_classifier)->toBe(4); // 2 correct classifiers × 2 pts
});

it('awards zero pts when user predicts wrong R1 classifiers', function () {
    $round = Round::factory()->r1()->create(['points_classifier' => 2]);
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

    (new CalculateClassifierPoints)->handle(new RoundFinalized($round));

    $submission = PredictionSubmission::where('user_id', $user->id)->where('round_id', $round->id)->first();
    expect($submission->pts_classifier)->toBe(0);
});

it('does not award classifier pts to draft submissions', function () {
    $round = Round::factory()->r1()->create(['points_classifier' => 2]);
    $user  = User::factory()->create();
    PredictionSubmission::factory()->create(['user_id' => $user->id, 'round_id' => $round->id, 'status' => 'draft']);

    ['fixtures' => $fixtures] = makeGroup($round, 'A', 1);
    setActualScores($fixtures, [[3,0],[3,0],[3,0],[1,0],[1,0],[1,0]]);
    createUserPredictions($user, $fixtures, [[3,0],[3,0],[3,0],[1,0],[1,0],[1,0]]);

    (new CalculateClassifierPoints)->handle(new RoundFinalized($round));

    $submission = PredictionSubmission::where('user_id', $user->id)->where('round_id', $round->id)->first();
    expect($submission->pts_classifier)->toBe(0);
});

it('updates user total_points after R1 classifier calculation', function () {
    $round = Round::factory()->r1()->create(['points_classifier' => 2]);
    $user  = User::factory()->create(['total_points' => 0]);
    PredictionSubmission::factory()->submitted()->create(['user_id' => $user->id, 'round_id' => $round->id]);

    ['fixtures' => $fixtures] = makeGroup($round, 'A', 1);
    setActualScores($fixtures, [[3,0],[3,0],[3,0],[1,0],[1,0],[1,0]]);
    createUserPredictions($user, $fixtures, [[3,0],[3,0],[3,0],[1,0],[1,0],[1,0]]);

    (new CalculateClassifierPoints)->handle(new RoundFinalized($round));

    expect($user->fresh()->total_points)->toBe(4);
});

it('awards classifier pts for correctly predicted 8-best-thirds across 9 groups', function () {
    $round = Round::factory()->r1()->create(['points_classifier' => 2]);
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

    (new CalculateClassifierPoints)->handle(new RoundFinalized($round));

    $submission = PredictionSubmission::where('user_id', $user->id)->where('round_id', $round->id)->first();
    // 9 groups × 2 top classifiers = 18 correct + 8 out of 9 thirds correct = 26 correct × 2 = 52 pts
    expect($submission->pts_classifier)->toBe(52);
});
