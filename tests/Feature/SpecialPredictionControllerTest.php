<?php

use App\Models\Player;
use App\Models\SpecialPrediction;
use App\Models\Team;
use App\Models\User;
use App\Models\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'user', 'is_active' => true]);
});

it('shows the special predictions form with relations', function () {
    $group  = Group::factory()->create(['name' => 'A']);
    $champ  = Team::factory()->create(['group_id' => $group->id]);
    $runner = Team::factory()->create(['group_id' => $group->id]);
    $scorer = Player::factory()->create(['team_id' => $champ->id]);

    SpecialPrediction::create([
        'user_id'              => $this->user->id,
        'champion_team_id'     => $champ->id,
        'runner_up_team_id'    => $runner->id,
        'top_scorer_player_id' => $scorer->id,
        'is_locked'            => false,
    ]);

    $response = $this->withoutVite()->actingAs($this->user)->get('/predictions/special');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Predictions/Special')
        ->has('teams', 2)
        ->has('players', 1)
        ->where('special.champion_team_id', $champ->id)
        ->where('special.champion.id', $champ->id)
        ->where('special.top_scorer.id', $scorer->id)
        ->where('special.top_scorer.team.id', $champ->id)
    );
});

it('saves special predictions', function () {
    $group   = Group::factory()->create(['name' => 'A']);
    $champ   = Team::factory()->create(['group_id' => $group->id]);
    $runner  = Team::factory()->create(['group_id' => $group->id]);
    $scorer  = Player::factory()->create(['team_id' => $champ->id]);

    $this->actingAs($this->user)->post('/predictions/special', [
        'champion_team_id'     => $champ->id,
        'runner_up_team_id'    => $runner->id,
        'top_scorer_player_id' => $scorer->id,
    ])->assertRedirect();

    $special = SpecialPrediction::where('user_id', $this->user->id)->first();
    expect($special)->not->toBeNull();
    expect($special->champion_team_id)->toBe($champ->id);
    expect($special->runner_up_team_id)->toBe($runner->id);
});

it('updates existing special prediction on re-save', function () {
    $group   = Group::factory()->create(['name' => 'A']);
    $champ   = Team::factory()->create(['group_id' => $group->id]);
    $runner  = Team::factory()->create(['group_id' => $group->id]);
    $newChamp = Team::factory()->create(['group_id' => $group->id]);
    $scorer  = Player::factory()->create(['team_id' => $champ->id]);

    SpecialPrediction::create([
        'user_id'              => $this->user->id,
        'champion_team_id'     => $champ->id,
        'runner_up_team_id'    => $runner->id,
        'top_scorer_player_id' => $scorer->id,
        'is_locked'            => false,
    ]);

    $this->actingAs($this->user)->post('/predictions/special', [
        'champion_team_id'     => $newChamp->id,
        'runner_up_team_id'    => $runner->id,
        'top_scorer_player_id' => $scorer->id,
    ])->assertRedirect();

    expect(SpecialPrediction::count())->toBe(1);
    expect(SpecialPrediction::first()->champion_team_id)->toBe($newChamp->id);
});

it('rejects save when champion equals runner-up', function () {
    $group  = Group::factory()->create(['name' => 'A']);
    $team   = Team::factory()->create(['group_id' => $group->id]);
    $scorer = Player::factory()->create(['team_id' => $team->id]);

    $this->actingAs($this->user)->post('/predictions/special', [
        'champion_team_id'     => $team->id,
        'runner_up_team_id'    => $team->id,
        'top_scorer_player_id' => $scorer->id,
    ])->assertSessionHasErrors('runner_up_team_id');
});

it('blocks save when special predictions are locked', function () {
    $group   = Group::factory()->create(['name' => 'A']);
    $champ   = Team::factory()->create(['group_id' => $group->id]);
    $runner  = Team::factory()->create(['group_id' => $group->id]);
    $scorer  = Player::factory()->create(['team_id' => $champ->id]);

    SpecialPrediction::create([
        'user_id'              => $this->user->id,
        'champion_team_id'     => $champ->id,
        'runner_up_team_id'    => $runner->id,
        'top_scorer_player_id' => $scorer->id,
        'is_locked'            => true,
    ]);

    $newChamp = Team::factory()->create(['group_id' => $group->id]);

    $this->actingAs($this->user)->post('/predictions/special', [
        'champion_team_id'     => $newChamp->id,
        'runner_up_team_id'    => $runner->id,
        'top_scorer_player_id' => $scorer->id,
    ])->assertRedirect();

    expect(SpecialPrediction::first()->champion_team_id)->toBe($champ->id); // unchanged
});
