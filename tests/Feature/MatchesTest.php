<?php

use App\Models\Fixture;
use App\Models\Group;
use App\Models\Round;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('authenticated user can view matches page', function () {
    $user = User::factory()->create(['is_active' => true]);

    $this->actingAs($user)
        ->get('/matches')
        ->assertInertia(fn ($page) => $page
            ->component('Matches')
            ->has('matchDays')
            ->has('groups')
            ->has('fifaRounds')
            ->has('defaultFifaRound')
        );
});

it('guest is redirected from matches page', function () {
    $this->get('/matches')->assertRedirect('/login');
});

it('formatFixture includes fifaRound matchNumber wentToET winner', function () {
    $user  = User::factory()->create(['is_active' => true]);
    $round = Round::factory()->create(['slug' => 'grupos', 'order' => 1]);
    $group = Group::factory()->create(['name' => 'A']);
    $teamA = Team::factory()->create(['group_id' => $group->id, 'fifa_code' => 'MEX']);
    $teamB = Team::factory()->create(['group_id' => $group->id, 'fifa_code' => 'USA']);

    Fixture::factory()->create([
        'round_id'           => $round->id,
        'group_id'           => $group->id,
        'match_number'       => 1,
        'home_team_id'       => $teamA->id,
        'away_team_id'       => $teamB->id,
        'home_score'         => 2,
        'away_score'         => 1,
        'winner_team_id'     => $teamA->id,
        'went_to_extra_time' => false,
        'status'             => 'finished',
    ]);

    $this->actingAs($user)
        ->get('/matches')
        ->assertInertia(fn ($page) => $page
            ->component('Matches')
            ->where('matchDays.0.matches.0.fifaRound', 'grupos')
            ->where('matchDays.0.matches.0.matchNumber', 1)
            ->where('matchDays.0.matches.0.winner', 'MEX')
            ->where('matchDays.0.matches.0.winnerFlagUrl', null)
        );
});

it('fifaRoundSlug derives correct slug from match_number ranges', function () {
    $user  = User::factory()->create(['is_active' => true]);
    $round = Round::factory()->create(['slug' => 'r32', 'order' => 2]);
    $group = Group::factory()->create(['name' => 'Z']);
    $teamA = Team::factory()->create(['group_id' => $group->id, 'fifa_code' => 'ARG']);
    $teamB = Team::factory()->create(['group_id' => $group->id, 'fifa_code' => 'BRA']);

    Fixture::factory()->create([
        'round_id'     => $round->id,
        'group_id'     => null,
        'match_number' => 73,
        'home_team_id' => $teamA->id,
        'away_team_id' => $teamB->id,
        'status'       => 'scheduled',
    ]);

    $this->actingAs($user)
        ->get('/matches')
        ->assertInertia(fn ($page) => $page
            ->where('matchDays.0.matches.0.fifaRound', 'r32')
        );
});

it('fifaRounds prop contains only rounds with fixtures', function () {
    $user  = User::factory()->create(['is_active' => true]);
    $round = Round::factory()->create(['slug' => 'grupos', 'order' => 1]);
    $group = Group::factory()->create(['name' => 'A']);
    $teamA = Team::factory()->create(['group_id' => $group->id]);
    $teamB = Team::factory()->create(['group_id' => $group->id]);

    Fixture::factory()->create([
        'round_id'     => $round->id,
        'group_id'     => $group->id,
        'match_number' => 1,
        'home_team_id' => $teamA->id,
        'away_team_id' => $teamB->id,
        'status'       => 'scheduled',
    ]);

    $this->actingAs($user)
        ->get('/matches')
        ->assertInertia(fn ($page) => $page
            ->has('fifaRounds', 1)
            ->where('fifaRounds.0.slug', 'grupos')
            ->where('fifaRounds.0.label', 'GRUPOS')
            ->where('fifaRounds.0.matchCount', 1)
        );
});

it('defaultFifaRound points to earliest non-finished fixture', function () {
    $user   = User::factory()->create(['is_active' => true]);
    $round1 = Round::factory()->create(['slug' => 'grupos', 'order' => 1]);
    $round2 = Round::factory()->create(['slug' => 'r32', 'order' => 2]);
    $group  = Group::factory()->create(['name' => 'A']);
    $teamA  = Team::factory()->create(['group_id' => $group->id]);
    $teamB  = Team::factory()->create(['group_id' => $group->id]);

    Fixture::factory()->create([
        'round_id' => $round1->id, 'group_id' => $group->id,
        'match_number' => 1, 'home_team_id' => $teamA->id,
        'away_team_id' => $teamB->id, 'status' => 'finished',
        'home_score' => 1, 'away_score' => 0,
    ]);

    Fixture::factory()->create([
        'round_id' => $round2->id, 'group_id' => null,
        'match_number' => 73, 'home_team_id' => $teamA->id,
        'away_team_id' => $teamB->id, 'status' => 'scheduled',
    ]);

    $this->actingAs($user)
        ->get('/matches')
        ->assertInertia(fn ($page) => $page
            ->where('defaultFifaRound', 'r32')
        );
});
