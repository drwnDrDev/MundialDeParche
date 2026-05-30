<?php

use App\Models\Fixture;
use App\Models\Player;
use App\Models\Prediction;
use App\Models\PredictionSubmission;
use App\Models\Round;
use App\Models\SpecialPrediction;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── Especiales en comprobante de R1 ──────────────────────────────────────────

it('includes specialPrediction prop in R1 receipt when it exists', function () {
    $this->withoutVite();
    $round  = Round::factory()->f1()->create(['is_locked' => true]);
    $user   = User::factory()->create(['role' => 'user', 'is_active' => true]);
    $team1  = Team::factory()->create();
    $team2  = Team::factory()->create();
    $player = Player::factory()->for($team1)->create();

    SpecialPrediction::factory()->create([
        'user_id'              => $user->id,
        'champion_team_id'     => $team1->id,
        'runner_up_team_id'    => $team2->id,
        'top_scorer_player_id' => $player->id,
        'is_locked'            => true,
    ]);

    PredictionSubmission::factory()->locked()->create([
        'user_id'  => $user->id,
        'round_id' => $round->id,
    ]);

    $this->actingAs($user)
        ->get(route('predictions.receipt', $round))
        ->assertInertia(fn ($page) => $page
            ->component('Predictions/Receipt')
            ->has('specialPrediction')
            ->where('specialPrediction.champion_team_id', $team1->id)
        );
});

it('specialPrediction prop is null for non-grupos rounds', function () {
    $this->withoutVite();
    $round = Round::factory()->f2()->create(['is_locked' => true]);
    $user  = User::factory()->create(['role' => 'user', 'is_active' => true]);

    PredictionSubmission::factory()->locked()->create([
        'user_id'  => $user->id,
        'round_id' => $round->id,
    ]);

    $this->actingAs($user)
        ->get(route('predictions.receipt', $round))
        ->assertInertia(fn ($page) => $page
            ->component('Predictions/Receipt')
            ->where('specialPrediction', null)
        );
});

// ── Comprobante público ───────────────────────────────────────────────────────

it('includes usersWithSubmission when round is locked', function () {
    $this->withoutVite();
    $round = Round::factory()->f1()->create(['is_locked' => true]);
    $user  = User::factory()->create(['role' => 'user', 'is_active' => true]);

    PredictionSubmission::factory()->locked()->create([
        'user_id'  => $user->id,
        'round_id' => $round->id,
    ]);

    $this->actingAs($user)
        ->get(route('predictions.receipt', $round))
        ->assertInertia(fn ($page) => $page
            ->has('usersWithSubmission')
            ->has('viewedUserId')
            ->has('authUserId')
        );
});

it('usersWithSubmission is null when round is not locked', function () {
    $this->withoutVite();
    $round = Round::factory()->f1()->create(['is_open' => true, 'is_locked' => false]);
    $user  = User::factory()->create(['role' => 'user', 'is_active' => true]);

    PredictionSubmission::factory()->create([
        'user_id'  => $user->id,
        'round_id' => $round->id,
        'status'   => 'submitted',
    ]);

    $this->actingAs($user)
        ->get(route('predictions.receipt', $round))
        ->assertInertia(fn ($page) => $page
            ->where('usersWithSubmission', null)
        );
});

it('can view another users receipt when round is locked', function () {
    $this->withoutVite();
    $round  = Round::factory()->f2()->create(['is_locked' => true]);
    $viewer = User::factory()->create(['role' => 'user', 'is_active' => true]);
    $owner  = User::factory()->create(['role' => 'user', 'is_active' => true]);

    PredictionSubmission::factory()->locked()->create([
        'user_id'  => $viewer->id,
        'round_id' => $round->id,
    ]);
    PredictionSubmission::factory()->locked()->create([
        'user_id'  => $owner->id,
        'round_id' => $round->id,
    ]);

    $this->actingAs($viewer)
        ->get(route('predictions.receipt', $round) . '?user_id=' . $owner->id)
        ->assertInertia(fn ($page) => $page
            ->where('viewedUserId', $owner->id)
            ->where('authUserId', $viewer->id)
        );
});

it('ignores user_id param when round is not locked', function () {
    $this->withoutVite();
    $round  = Round::factory()->f1()->create(['is_open' => true, 'is_locked' => false]);
    $viewer = User::factory()->create(['role' => 'user', 'is_active' => true]);
    $owner  = User::factory()->create(['role' => 'user', 'is_active' => true]);

    PredictionSubmission::factory()->create([
        'user_id'  => $viewer->id,
        'round_id' => $round->id,
        'status'   => 'submitted',
    ]);

    $this->actingAs($viewer)
        ->get(route('predictions.receipt', $round) . '?user_id=' . $owner->id)
        ->assertInertia(fn ($page) => $page
            ->where('viewedUserId', $viewer->id)
        );
});

it('falls back to auth user if requested user_id has no submission', function () {
    $this->withoutVite();
    $round  = Round::factory()->f1()->create(['is_locked' => true]);
    $viewer = User::factory()->create(['role' => 'user', 'is_active' => true]);
    $other  = User::factory()->create(['role' => 'user', 'is_active' => true]);

    PredictionSubmission::factory()->locked()->create([
        'user_id'  => $viewer->id,
        'round_id' => $round->id,
    ]);
    // $other has no submission for this round

    $this->actingAs($viewer)
        ->get(route('predictions.receipt', $round) . '?user_id=' . $other->id)
        ->assertInertia(fn ($page) => $page
            ->where('viewedUserId', $viewer->id)
        );
});
