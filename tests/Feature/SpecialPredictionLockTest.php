<?php

use App\Models\Round;
use App\Models\SpecialPrediction;
use App\Models\Team;
use App\Models\Player;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('locking the grupos round sets is_locked=true on all special predictions', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user1 = User::factory()->create(['role' => 'user']);
    $user2 = User::factory()->create(['role' => 'user']);
    $round = Round::factory()->f1()->create(['is_open' => true, 'is_locked' => false]);

    SpecialPrediction::factory()->create(['user_id' => $user1->id, 'is_locked' => false]);
    SpecialPrediction::factory()->create(['user_id' => $user2->id, 'is_locked' => false]);

    $this->actingAs($admin)->post("/admin/rounds/{$round->slug}/lock");

    expect(SpecialPrediction::where('is_locked', false)->count())->toBe(0);
    expect(SpecialPrediction::where('is_locked', true)->count())->toBe(2);
});

it('locking a non-grupos round does not affect special predictions', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user  = User::factory()->create(['role' => 'user']);
    $r1    = Round::factory()->f1()->create(['is_locked' => true]);
    $r2    = Round::factory()->f2()->create(['is_open' => true, 'is_locked' => false]);

    SpecialPrediction::factory()->create(['user_id' => $user->id, 'is_locked' => true]);

    $this->actingAs($admin)->post("/admin/rounds/{$r2->slug}/lock");

    expect(SpecialPrediction::where('is_locked', true)->count())->toBe(1);
});

it('cannot save special predictions when grupos round is locked and no record exists', function () {
    $round  = Round::factory()->f1()->create(['is_locked' => true]);
    $user   = User::factory()->create(['role' => 'user', 'is_active' => true, 'is_activated' => true]);
    $team1  = Team::factory()->create();
    $team2  = Team::factory()->create();
    $player = Player::factory()->for($team1)->create();

    $this->actingAs($user)
        ->post(route('predictions.special.save'), [
            'champion_team_id'     => $team1->id,
            'runner_up_team_id'    => $team2->id,
            'top_scorer_player_id' => $player->id,
        ])
        ->assertSessionHas('status');

    expect(SpecialPrediction::where('user_id', $user->id)->count())->toBe(0);
});

it('can save special predictions when grupos round is open', function () {
    Round::factory()->f1()->create(['is_open' => true, 'is_locked' => false]);
    $user   = User::factory()->create(['role' => 'user', 'is_active' => true, 'is_activated' => true]);
    $team1  = Team::factory()->create();
    $team2  = Team::factory()->create();
    $player = Player::factory()->for($team1)->create();

    $this->actingAs($user)
        ->post(route('predictions.special.save'), [
            'champion_team_id'     => $team1->id,
            'runner_up_team_id'    => $team2->id,
            'top_scorer_player_id' => $player->id,
        ])
        ->assertSessionHas('status');

    expect(SpecialPrediction::where('user_id', $user->id)->count())->toBe(1);
});
