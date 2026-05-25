<?php

use App\Events\TournamentFinalized;
use App\Models\Group;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
});

it('shows the tournament finalization form', function () {
    $group = Group::factory()->create();
    Team::factory()->create(['group_id' => $group->id]);
    Player::factory()->create();

    $response = $this->withoutVite()->actingAs($this->admin)->get('/admin/tournament');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Tournament')
        ->has('teams')
        ->has('players')
    );
});

it('dispatches TournamentFinalized on admin form submission', function () {
    Event::fake();
    $group  = Group::factory()->create();
    $champ  = Team::factory()->create(['group_id' => $group->id]);
    $runner = Team::factory()->create(['group_id' => $group->id]);
    $scorer = Player::factory()->create();

    $this->actingAs($this->admin)->post('/admin/tournament/finalize', [
        'champion_team_id'     => $champ->id,
        'runner_up_team_id'    => $runner->id,
        'top_scorer_player_id' => $scorer->id,
    ])->assertRedirect();

    Event::assertDispatched(TournamentFinalized::class, function ($event) use ($champ, $runner, $scorer) {
        return $event->championTeamId === $champ->id
            && $event->runnerUpTeamId === $runner->id
            && $event->topScorerPlayerId === $scorer->id;
    });
});

it('validates champion and runner-up must be different teams', function () {
    Event::fake();
    $group  = Group::factory()->create();
    $champ  = Team::factory()->create(['group_id' => $group->id]);
    $scorer = Player::factory()->create();

    $this->actingAs($this->admin)->post('/admin/tournament/finalize', [
        'champion_team_id'     => $champ->id,
        'runner_up_team_id'    => $champ->id, // same!
        'top_scorer_player_id' => $scorer->id,
    ])->assertSessionHasErrors('runner_up_team_id');

    Event::assertNotDispatched(TournamentFinalized::class);
});
