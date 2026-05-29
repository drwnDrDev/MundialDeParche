<?php

use App\Models\Round;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'user', 'is_active' => true]);
});

it('lists rounds with user submission status', function () {
    $open   = Round::factory()->f1()->create(['is_open' => true]);
    $closed = Round::factory()->f2()->create(['is_open' => false, 'order' => 2]);

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
    $round   = Round::factory()->f1()->create(['is_open' => true]);
    $group   = \App\Models\Group::factory()->create(['name' => 'A']);
    $home    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $away    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    \App\Models\Fixture::factory()->create([
        'round_id'     => $round->id,
        'group_id'     => $group->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
    ]);

    $response = $this->withoutVite()->actingAs($this->user)->get("/predictions/{$round->slug}");

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
    $round = Round::factory()->f2()->create(['is_open' => true]);
    \App\Models\Fixture::factory()->create([
        'round_id'     => $round->id,
        'home_team_id' => null,
        'away_team_id' => null,
    ]);

    $this->actingAs($this->user)->get("/predictions/{$round->slug}")
        ->assertRedirect(route('predictions.index'));
});

it('shows locked page when round is closed', function () {
    $round   = Round::factory()->f1()->create(['is_open' => false, 'is_locked' => true]);
    $group   = \App\Models\Group::factory()->create(['name' => 'A']);
    $home    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $away    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    \App\Models\Fixture::factory()->create([
        'round_id'     => $round->id,
        'group_id'     => $group->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
    ]);

    $response = $this->withoutVite()->actingAs($this->user)->get("/predictions/{$round->slug}");

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Predictions/Locked')->has('roundName')->has('isLocked'));
});

it('saves predictions as draft', function () {
    $round   = Round::factory()->f1()->create(['is_open' => true]);
    $group   = \App\Models\Group::factory()->create(['name' => 'A']);
    $home    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $away    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $fixture = \App\Models\Fixture::factory()->create([
        'round_id'     => $round->id,
        'group_id'     => $group->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
    ]);

    $this->actingAs($this->user)->post("/predictions/{$round->slug}/save", [
        'predictions' => [
            (string) $fixture->id => ['predicted_home' => 2, 'predicted_away' => 1],
        ],
    ])->assertRedirect();

    expect(\App\Models\Prediction::count())->toBe(1);
    expect(\App\Models\Prediction::first()->predicted_home)->toBe(2);
    expect(\App\Models\PredictionSubmission::first()->status)->toBe('draft');
});

it('auto-promotes to submitted with classifiers when all R1 fixtures are covered', function () {
    $round = Round::factory()->f1()->create(['is_open' => true]);
    $group = \App\Models\Group::factory()->create(['name' => 'A']);
    $home  = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $away  = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $f1    = \App\Models\Fixture::factory()->create([
        'round_id' => $round->id, 'group_id' => $group->id,
        'home_team_id' => $home->id, 'away_team_id' => $away->id,
    ]);

    $classifiers = [['team_id' => $home->id, 'group' => 'A', 'position' => 1]];

    $this->actingAs($this->user)->post("/predictions/{$round->slug}/save", [
        'predictions' => [
            (string) $f1->id => ['predicted_home' => 2, 'predicted_away' => 0],
        ],
        'predicted_classifiers' => $classifiers,
    ])->assertRedirect();

    $submission = \App\Models\PredictionSubmission::first();
    expect($submission->status)->toBe('submitted');
    expect($submission->submitted_at)->not->toBeNull();
    expect($submission->predicted_classifiers)->toEqual($classifiers);
});

it('stays draft when predicted_classifiers is missing even if all fixtures covered', function () {
    $round = Round::factory()->f1()->create(['is_open' => true]);
    $group = \App\Models\Group::factory()->create(['name' => 'A']);
    $home  = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $away  = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $f1    = \App\Models\Fixture::factory()->create([
        'round_id' => $round->id, 'group_id' => $group->id,
        'home_team_id' => $home->id, 'away_team_id' => $away->id,
    ]);

    $this->actingAs($this->user)->post("/predictions/{$round->slug}/save", [
        'predictions' => [
            (string) $f1->id => ['predicted_home' => 2, 'predicted_away' => 0],
        ],
        // no predicted_classifiers
    ])->assertRedirect();

    expect(\App\Models\PredictionSubmission::first()->status)->toBe('draft');
    expect(\App\Models\PredictionSubmission::first()->predicted_classifiers)->toBeNull();
});

it('stays draft for non-group rounds regardless of classifiers payload', function () {
    $round = Round::factory()->f2()->create(['is_open' => true]);
    $home  = \App\Models\Team::factory()->create();
    $away  = \App\Models\Team::factory()->create();
    $f1    = \App\Models\Fixture::factory()->create([
        'round_id' => $round->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
    ]);

    $this->actingAs($this->user)->post("/predictions/{$round->slug}/save", [
        'predictions' => [
            (string) $f1->id => ['predicted_home' => 2, 'predicted_away' => 1],
        ],
    ])->assertRedirect();

    expect(\App\Models\PredictionSubmission::first()->status)->toBe('draft');
});

it('updates existing prediction on save', function () {
    $round   = Round::factory()->f1()->create(['is_open' => true]);
    $group   = \App\Models\Group::factory()->create(['name' => 'A']);
    $home    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $away    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $fixture = \App\Models\Fixture::factory()->create([
        'round_id' => $round->id, 'group_id' => $group->id,
        'home_team_id' => $home->id, 'away_team_id' => $away->id,
    ]);
    \App\Models\Prediction::factory()->create([
        'user_id' => $this->user->id, 'match_id' => $fixture->id,
        'predicted_home' => 0, 'predicted_away' => 0,
    ]);

    $this->actingAs($this->user)->post("/predictions/{$round->slug}/save", [
        'predictions' => [
            (string) $fixture->id => ['predicted_home' => 3, 'predicted_away' => 1],
        ],
    ])->assertRedirect();

    expect(\App\Models\Prediction::count())->toBe(1);
    expect(\App\Models\Prediction::first()->predicted_home)->toBe(3);
});

it('rejects save when round is not open', function () {
    $round   = Round::factory()->f1()->create(['is_open' => false]);
    $group   = \App\Models\Group::factory()->create();
    $fixture = \App\Models\Fixture::factory()->create([
        'round_id' => $round->id, 'group_id' => $group->id,
    ]);

    $this->actingAs($this->user)->post("/predictions/{$round->slug}/save", [
        'predictions' => [(string) $fixture->id => ['predicted_home' => 1, 'predicted_away' => 0]],
    ])->assertRedirect();

    expect(\App\Models\Prediction::count())->toBe(0);
});

it('rejects save when submission is locked', function () {
    $round   = Round::factory()->f1()->create(['is_open' => true, 'is_locked' => true]);
    $group   = \App\Models\Group::factory()->create(['name' => 'A']);
    $home    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $away    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $fixture = \App\Models\Fixture::factory()->create([
        'round_id' => $round->id, 'group_id' => $group->id,
        'home_team_id' => $home->id, 'away_team_id' => $away->id,
    ]);
    \App\Models\PredictionSubmission::factory()->locked()->create([
        'user_id' => $this->user->id, 'round_id' => $round->id,
    ]);

    $this->actingAs($this->user)->post("/predictions/{$round->slug}/save", [
        'predictions' => [(string) $fixture->id => ['predicted_home' => 1, 'predicted_away' => 0]],
    ])->assertRedirect();

    expect(\App\Models\Prediction::count())->toBe(0);
});

it('submits predictions when all fixtures are covered', function () {
    $round   = Round::factory()->f1()->create(['is_open' => true]);
    $group   = \App\Models\Group::factory()->create(['name' => 'A']);
    $home    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $away    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $fixture = \App\Models\Fixture::factory()->create([
        'round_id' => $round->id, 'group_id' => $group->id,
        'home_team_id' => $home->id, 'away_team_id' => $away->id,
    ]);

    $this->actingAs($this->user)->post("/predictions/{$round->slug}/save", [
        'predictions' => [
            (string) $fixture->id => ['predicted_home' => 2, 'predicted_away' => 1],
        ],
        'predicted_classifiers' => [
            ['team_id' => $home->id, 'group' => 'A', 'position' => 1],
            ['team_id' => $away->id, 'group' => 'A', 'position' => 2],
        ],
    ])->assertRedirect();

    expect(\App\Models\PredictionSubmission::first()->status)->toBe('submitted');
    expect(\App\Models\PredictionSubmission::first()->submitted_at)->not->toBeNull();
});

it('rejects submit when not all fixtures covered', function () {
    $round   = Round::factory()->f1()->create(['is_open' => true]);
    $group   = \App\Models\Group::factory()->create(['name' => 'A']);
    $home    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $away    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    \App\Models\Fixture::factory()->create([
        'round_id' => $round->id, 'group_id' => $group->id,
        'home_team_id' => $home->id, 'away_team_id' => $away->id,
    ]);

    // Send predictions for zero fixtures + no classifiers → 'required' validation error
    $this->actingAs($this->user)->post("/predictions/{$round->slug}/save", [
        'predictions' => [],
    ])->assertSessionHasErrors('predictions');

    expect(\App\Models\PredictionSubmission::count())->toBe(0);
});

it('rejects submit with tie in knockout round', function () {
    $round   = Round::factory()->f2()->create(['is_open' => true]);
    $group   = \App\Models\Group::factory()->create(['name' => 'A']);
    $home    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $away    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $fixture = \App\Models\Fixture::factory()->create([
        'round_id' => $round->id,
        'home_team_id' => $home->id, 'away_team_id' => $away->id,
    ]);

    $this->actingAs($this->user)->post("/predictions/{$round->slug}/save", [
        'predictions' => [
            (string) $fixture->id => ['predicted_home' => 1, 'predicted_away' => 1],
        ],
    ])->assertSessionHasErrors('predictions');

    expect(\App\Models\PredictionSubmission::count())->toBe(0);
});

it('allows ties in group stage (R1) submit', function () {
    $round   = Round::factory()->f1()->create(['is_open' => true]);
    $group   = \App\Models\Group::factory()->create(['name' => 'A']);
    $home    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $away    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $fixture = \App\Models\Fixture::factory()->create([
        'round_id' => $round->id, 'group_id' => $group->id,
        'home_team_id' => $home->id, 'away_team_id' => $away->id,
    ]);

    $this->actingAs($this->user)->post("/predictions/{$round->slug}/save", [
        'predictions' => [
            (string) $fixture->id => ['predicted_home' => 1, 'predicted_away' => 1],
        ],
    ])->assertRedirect();

    expect(\App\Models\PredictionSubmission::first()->status)->toBe('draft');
});

it('rejects save (draft) with tie in knockout round', function () {
    $round   = Round::factory()->f2()->create(['is_open' => true]);
    $group   = \App\Models\Group::factory()->create(['name' => 'A']);
    $home    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $away    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $fixture = \App\Models\Fixture::factory()->create([
        'round_id'     => $round->id,
        'home_team_id' => $home->id, 'away_team_id' => $away->id,
    ]);

    $this->actingAs($this->user)->post("/predictions/{$round->slug}/save", [
        'predictions' => [
            (string) $fixture->id => ['predicted_home' => 1, 'predicted_away' => 1],
        ],
    ])->assertSessionHasErrors('predictions');

    expect(\App\Models\Prediction::count())->toBe(0);
});

it('allows ties in group stage (R1) save', function () {
    $round   = Round::factory()->f1()->create(['is_open' => true]);
    $group   = \App\Models\Group::factory()->create(['name' => 'A']);
    $home    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $away    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $fixture = \App\Models\Fixture::factory()->create([
        'round_id' => $round->id, 'group_id' => $group->id,
        'home_team_id' => $home->id, 'away_team_id' => $away->id,
    ]);

    $this->actingAs($this->user)->post("/predictions/{$round->slug}/save", [
        'predictions' => [
            (string) $fixture->id => ['predicted_home' => 1, 'predicted_away' => 1],
        ],
    ])->assertRedirect();

    expect(\App\Models\Prediction::first()->predicted_home)->toBe(1)
        ->and(\App\Models\Prediction::first()->predicted_away)->toBe(1);
});

// ── phasePts en index ─────────────────────────────────────────────────────

it('index includes phasePts with zeros for rounds with no predictions', function () {
    $round = Round::factory()->f1()->create();

    $this->actingAs($this->user)
        ->get(route('predictions.index'))
        ->assertInertia(fn ($page) => $page
            ->component('Predictions/Index')
            ->has('phasePts')
            ->where("phasePts.{$round->id}.pts_exact", 0)
            ->where("phasePts.{$round->id}.pts_result", 0)
            ->where("phasePts.{$round->id}.pts_classifier", 0)
            ->where("phasePts.{$round->id}.total", 0)
            ->where("phasePts.{$round->id}.prediction_count", 0)
        );
});

it('index phasePts sums pts_exact and pts_result from predictions', function () {
    $round   = Round::factory()->f1()->create(['points_classifier' => 2]);
    $group   = \App\Models\Group::factory()->create();
    $fixture = \App\Models\Fixture::factory()->create([
        'round_id' => $round->id, 'group_id' => $group->id,
    ]);

    \App\Models\Prediction::factory()->create([
        'user_id'      => $this->user->id,
        'match_id'     => $fixture->id,
        'pts_exact'    => 3,
        'pts_result'   => 1,
        'total_points' => 4,
    ]);

    \App\Models\PredictionSubmission::factory()->submitted()->create([
        'user_id'        => $this->user->id,
        'round_id'       => $round->id,
        'pts_classifier' => 6,
    ]);

    $this->actingAs($this->user)
        ->get(route('predictions.index'))
        ->assertInertia(fn ($page) => $page
            ->where("phasePts.{$round->id}.pts_exact", 3)
            ->where("phasePts.{$round->id}.pts_result", 1)
            ->where("phasePts.{$round->id}.pts_classifier", 6)
            ->where("phasePts.{$round->id}.total", 10)
            ->where("phasePts.{$round->id}.prediction_count", 1)
        );
});

it('index includes fixtures_count on each round', function () {
    $round = Round::factory()->f1()->create();
    $group = \App\Models\Group::factory()->create();
    \App\Models\Fixture::factory(3)->create(['round_id' => $round->id, 'group_id' => $group->id]);

    $this->actingAs($this->user)
        ->get(route('predictions.index'))
        ->assertInertia(fn ($page) => $page
            ->where("rounds.0.fixtures_count", 3)
        );
});

// ── receipt ───────────────────────────────────────────────────────────────

it('receipt renders when submission exists', function () {
    $round = Round::factory()->f1()->create(['is_open' => true]);
    \App\Models\PredictionSubmission::factory()->submitted()->create([
        'user_id' => $this->user->id, 'round_id' => $round->id,
    ]);

    $this->withoutVite()->actingAs($this->user)
        ->get(route('predictions.receipt', $round->slug))
        ->assertInertia(fn ($page) => $page->component('Predictions/Receipt'));
});

it('receipt redirects to index when no submission exists', function () {
    $round = Round::factory()->f1()->create();

    $this->actingAs($this->user)
        ->get(route('predictions.receipt', $round->slug))
        ->assertRedirect(route('predictions.index'));
});

it('receipt sets isFinalized true when round is locked', function () {
    $round = Round::factory()->f1()->create(['is_locked' => true, 'is_open' => false]);
    \App\Models\PredictionSubmission::factory()->locked()->create([
        'user_id' => $this->user->id, 'round_id' => $round->id,
    ]);

    $this->withoutVite()->actingAs($this->user)
        ->get(route('predictions.receipt', $round->slug))
        ->assertInertia(fn ($page) => $page->where('isFinalized', true));
});

it('receipt sets isFinalized false when round is not locked', function () {
    $round = Round::factory()->f1()->create(['is_open' => true, 'is_locked' => false]);
    \App\Models\PredictionSubmission::factory()->submitted()->create([
        'user_id' => $this->user->id, 'round_id' => $round->id,
    ]);

    $this->withoutVite()->actingAs($this->user)
        ->get(route('predictions.receipt', $round->slug))
        ->assertInertia(fn ($page) => $page->where('isFinalized', false));
});

it('receipt includes fixtures and user predictions keyed by match_id', function () {
    $round   = Round::factory()->f1()->create(['is_open' => true]);
    $group   = \App\Models\Group::factory()->create();
    $fixture = \App\Models\Fixture::factory()->create([
        'round_id' => $round->id, 'group_id' => $group->id,
    ]);

    \App\Models\PredictionSubmission::factory()->submitted()->create([
        'user_id' => $this->user->id, 'round_id' => $round->id,
    ]);

    \App\Models\Prediction::factory()->create([
        'user_id'        => $this->user->id,
        'match_id'       => $fixture->id,
        'predicted_home' => 2,
        'predicted_away' => 1,
    ]);

    $this->withoutVite()->actingAs($this->user)
        ->get(route('predictions.receipt', $round->slug))
        ->assertInertia(fn ($page) => $page
            ->has('fixtures', 1)
            ->has("predictions.{$fixture->id}")
            ->where("predictions.{$fixture->id}.predicted_home", 2)
            ->where("predictions.{$fixture->id}.predicted_away", 1)
        );
});

it('receipt includes predicted_classifiers enriched with team data for R1', function () {
    $round = Round::factory()->f1()->create(['is_open' => false, 'is_locked' => false]);
    $group = \App\Models\Group::factory()->create(['name' => 'A']);
    $home  = \App\Models\Team::factory()->create(['group_id' => $group->id, 'name' => 'Colombia', 'flag_url' => '/flags/col.png']);
    $away  = \App\Models\Team::factory()->create(['group_id' => $group->id, 'name' => 'Brasil']);

    \App\Models\PredictionSubmission::factory()->submitted()->create([
        'user_id'  => $this->user->id,
        'round_id' => $round->id,
        'predicted_classifiers' => [
            ['team_id' => $home->id, 'group' => 'A', 'position' => 1],
            ['team_id' => $away->id, 'group' => 'A', 'position' => 2],
        ],
    ]);

    $response = $this->withoutVite()->actingAs($this->user)
        ->get("/predictions/{$round->slug}/receipt");

    $response->assertInertia(fn ($page) => $page
        ->component('Predictions/Receipt')
        ->has('classifiers', 2)
        ->where('classifiers.0.team_name', 'Colombia')
        ->where('classifiers.0.flag_url', '/flags/col.png')
        ->where('classifiers.0.position', 1)
        ->missing('realClassifierIds')
    );
});

it('receipt includes realClassifierIds when round is finalized', function () {
    $round = Round::factory()->f1()->create(['is_open' => false, 'is_locked' => true]);
    $group = \App\Models\Group::factory()->create(['name' => 'A']);
    $home  = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $away  = \App\Models\Team::factory()->create(['group_id' => $group->id]);

    \App\Models\Fixture::factory()->create([
        'round_id'     => $round->id,
        'group_id'     => $group->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'home_score'   => 2,
        'away_score'   => 0,
    ]);

    \App\Models\PredictionSubmission::factory()->submitted()->create([
        'user_id'  => $this->user->id,
        'round_id' => $round->id,
        'predicted_classifiers' => [
            ['team_id' => $home->id, 'group' => 'A', 'position' => 1],
        ],
    ]);

    $response = $this->withoutVite()->actingAs($this->user)
        ->get("/predictions/{$round->slug}/receipt");

    $response->assertInertia(fn ($page) => $page
        ->component('Predictions/Receipt')
        ->has('realClassifierIds')
        ->where('realClassifierIds', fn ($ids) => in_array($home->id, is_array($ids) ? $ids : $ids->toArray()))
    );
});
