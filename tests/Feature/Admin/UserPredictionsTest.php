<?php

use App\Models\Fixture;
use App\Models\Prediction;
use App\Models\PredictionSubmission;
use App\Models\Round;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders predictions page for a given user', function () {
    $admin  = User::factory()->create(['role' => 'admin']);
    $target = User::factory()->create(['role' => 'user']);
    $round  = Round::factory()->f1()->create(['is_locked' => true]);
    $home   = Team::factory()->create(['fifa_code' => 'COL']);
    $away   = Team::factory()->create(['fifa_code' => 'BRA']);

    $fixture = Fixture::factory()->create([
        'round_id'     => $round->id,
        'match_number' => 1,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'home_score'   => 2,
        'away_score'   => 1,
        'status'       => 'finished',
    ]);

    Prediction::factory()->create([
        'user_id'        => $target->id,
        'match_id'       => $fixture->id,
        'predicted_home' => 2,
        'predicted_away' => 0,
        'pts_exact'      => 0,
        'pts_result'     => 1,
    ]);

    PredictionSubmission::factory()->create([
        'user_id'  => $target->id,
        'round_id' => $round->id,
        'status'   => 'locked',
    ]);

    $this->withoutVite()->actingAs($admin)
        ->get(route('admin.users.predictions', $target->id))
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('Admin/Users/Predictions')
            ->has('targetUser')
            ->has('rounds')
            ->has('fixtures')
            ->has('predictions')
            ->has('submissions')
        );
});

it('blocks non-admin from viewing user predictions', function () {
    $user   = User::factory()->create(['role' => 'user']);
    $target = User::factory()->create(['role' => 'user']);

    $this->actingAs($user)
        ->get(route('admin.users.predictions', $target->id))
        ->assertForbidden();
});
