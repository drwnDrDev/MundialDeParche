<?php

use App\Models\Round;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'user', 'is_active' => true]);
});

it('lists rounds with user submission status', function () {
    $open   = Round::factory()->r1()->create(['is_open' => true]);
    $closed = Round::factory()->r2()->create(['is_open' => false, 'order' => 2]);

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
    $round   = Round::factory()->r1()->create(['is_open' => true]);
    $group   = \App\Models\Group::factory()->create(['name' => 'A']);
    $home    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $away    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    \App\Models\Fixture::factory()->create([
        'round_id'     => $round->id,
        'group_id'     => $group->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
    ]);

    $response = $this->withoutVite()->actingAs($this->user)->get("/predictions/{$round->id}");

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
    $round = Round::factory()->r2()->create(['is_open' => true]);
    \App\Models\Fixture::factory()->create([
        'round_id'     => $round->id,
        'home_team_id' => null,
        'away_team_id' => null,
    ]);

    $this->actingAs($this->user)->get("/predictions/{$round->id}")
        ->assertRedirect(route('predictions.index'));
});

it('shows locked page when round is closed', function () {
    $round   = Round::factory()->r1()->create(['is_open' => false, 'is_locked' => true]);
    $group   = \App\Models\Group::factory()->create(['name' => 'A']);
    $home    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $away    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    \App\Models\Fixture::factory()->create([
        'round_id'     => $round->id,
        'group_id'     => $group->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
    ]);

    $response = $this->withoutVite()->actingAs($this->user)->get("/predictions/{$round->id}");

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Predictions/Locked')->has('roundName')->has('isLocked'));
});

it('saves predictions as draft', function () {
    $round   = Round::factory()->r1()->create(['is_open' => true]);
    $group   = \App\Models\Group::factory()->create(['name' => 'A']);
    $home    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $away    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $fixture = \App\Models\Fixture::factory()->create([
        'round_id'     => $round->id,
        'group_id'     => $group->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
    ]);

    $this->actingAs($this->user)->post("/predictions/{$round->id}/save", [
        'predictions' => [
            (string) $fixture->id => ['predicted_home' => 2, 'predicted_away' => 1],
        ],
    ])->assertRedirect();

    expect(\App\Models\Prediction::count())->toBe(1);
    expect(\App\Models\Prediction::first()->predicted_home)->toBe(2);
    expect(\App\Models\PredictionSubmission::first()->status)->toBe('draft');
});

it('updates existing prediction on save', function () {
    $round   = Round::factory()->r1()->create(['is_open' => true]);
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

    $this->actingAs($this->user)->post("/predictions/{$round->id}/save", [
        'predictions' => [
            (string) $fixture->id => ['predicted_home' => 3, 'predicted_away' => 1],
        ],
    ])->assertRedirect();

    expect(\App\Models\Prediction::count())->toBe(1);
    expect(\App\Models\Prediction::first()->predicted_home)->toBe(3);
});

it('rejects save when round is not open', function () {
    $round   = Round::factory()->r1()->create(['is_open' => false]);
    $group   = \App\Models\Group::factory()->create();
    $fixture = \App\Models\Fixture::factory()->create([
        'round_id' => $round->id, 'group_id' => $group->id,
    ]);

    $this->actingAs($this->user)->post("/predictions/{$round->id}/save", [
        'predictions' => [(string) $fixture->id => ['predicted_home' => 1, 'predicted_away' => 0]],
    ])->assertRedirect();

    expect(\App\Models\Prediction::count())->toBe(0);
});

it('rejects save when submission is locked', function () {
    $round   = Round::factory()->r1()->create(['is_open' => true, 'is_locked' => true]);
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

    $this->actingAs($this->user)->post("/predictions/{$round->id}/save", [
        'predictions' => [(string) $fixture->id => ['predicted_home' => 1, 'predicted_away' => 0]],
    ])->assertRedirect();

    expect(\App\Models\Prediction::count())->toBe(0);
});

it('submits predictions when all fixtures are covered', function () {
    $round   = Round::factory()->r1()->create(['is_open' => true]);
    $group   = \App\Models\Group::factory()->create(['name' => 'A']);
    $home    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $away    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $fixture = \App\Models\Fixture::factory()->create([
        'round_id' => $round->id, 'group_id' => $group->id,
        'home_team_id' => $home->id, 'away_team_id' => $away->id,
    ]);

    $this->actingAs($this->user)->post("/predictions/{$round->id}/submit", [
        'predictions' => [
            (string) $fixture->id => ['predicted_home' => 2, 'predicted_away' => 2],
        ],
    ])->assertRedirect(route('predictions.index'));

    expect(\App\Models\PredictionSubmission::first()->status)->toBe('submitted');
    expect(\App\Models\PredictionSubmission::first()->submitted_at)->not->toBeNull();
});

it('rejects submit when not all fixtures covered', function () {
    $round   = Round::factory()->r1()->create(['is_open' => true]);
    $group   = \App\Models\Group::factory()->create(['name' => 'A']);
    $home    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $away    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    \App\Models\Fixture::factory()->create([
        'round_id' => $round->id, 'group_id' => $group->id,
        'home_team_id' => $home->id, 'away_team_id' => $away->id,
    ]);

    $this->actingAs($this->user)->post("/predictions/{$round->id}/submit", [
        'predictions' => [], // no predictions
    ])->assertSessionHasErrors('predictions');

    expect(\App\Models\PredictionSubmission::count())->toBe(0);
});

it('rejects submit with tie in knockout round', function () {
    $round   = Round::factory()->r2()->create(['is_open' => true]);
    $group   = \App\Models\Group::factory()->create(['name' => 'A']);
    $home    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $away    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $fixture = \App\Models\Fixture::factory()->create([
        'round_id' => $round->id,
        'home_team_id' => $home->id, 'away_team_id' => $away->id,
    ]);

    $this->actingAs($this->user)->post("/predictions/{$round->id}/submit", [
        'predictions' => [
            (string) $fixture->id => ['predicted_home' => 1, 'predicted_away' => 1],
        ],
    ])->assertSessionHasErrors('predictions');

    expect(\App\Models\PredictionSubmission::count())->toBe(0);
});

it('allows ties in group stage (R1) submit', function () {
    $round   = Round::factory()->r1()->create(['is_open' => true]);
    $group   = \App\Models\Group::factory()->create(['name' => 'A']);
    $home    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $away    = \App\Models\Team::factory()->create(['group_id' => $group->id]);
    $fixture = \App\Models\Fixture::factory()->create([
        'round_id' => $round->id, 'group_id' => $group->id,
        'home_team_id' => $home->id, 'away_team_id' => $away->id,
    ]);

    $this->actingAs($this->user)->post("/predictions/{$round->id}/submit", [
        'predictions' => [
            (string) $fixture->id => ['predicted_home' => 1, 'predicted_away' => 1],
        ],
    ])->assertRedirect(route('predictions.index'));

    expect(\App\Models\PredictionSubmission::first()->status)->toBe('submitted');
});
