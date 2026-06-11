<?php

use App\Events\RoundFinalized;
use App\Events\RoundLocked;
use App\Models\Round;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
});

it('lists rounds', function () {
    Round::factory()->f1()->create();

    $response = $this->withoutVite()->actingAs($this->admin)->get('/admin/rounds');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Rounds/Index')
        ->has('rounds', 1)
    );
});

it('opens a round', function () {
    $round = Round::factory()->f1()->create(['is_open' => false]);

    $this->actingAs($this->admin)->post("/admin/rounds/{$round->slug}/open");

    expect($round->fresh()->is_open)->toBeTrue();
});

it('locks a round', function () {
    $round = Round::factory()->f1()->create(['is_open' => true, 'is_locked' => false]);

    $this->actingAs($this->admin)->post("/admin/rounds/{$round->slug}/lock");

    expect($round->fresh()->is_locked)->toBeTrue();
    expect($round->fresh()->is_open)->toBeFalse();
});

it('finalizes a round', function () {
    Event::fake([RoundFinalized::class, RoundLocked::class]);

    $round = Round::factory()->f1()->create(['is_open' => true, 'is_locked' => false]);

    $this->actingAs($this->admin)->post("/admin/rounds/{$round->slug}/finalize");

    Event::assertDispatched(RoundLocked::class);
    Event::assertDispatched(RoundFinalized::class);

    expect($round->fresh()->is_locked)->toBeTrue();
    expect($round->fresh()->is_open)->toBeFalse();
});

it('does not reopen a locked round', function () {
    $round = Round::factory()->f1()->create(['is_open' => false, 'is_locked' => true]);

    $this->actingAs($this->admin)->post("/admin/rounds/{$round->slug}/open")->assertRedirect();

    expect($round->fresh()->is_open)->toBeFalse();
});

it('finalizes a locked round and dispatches RoundFinalized', function () {
    Event::fake([RoundFinalized::class, RoundLocked::class]);

    $admin = User::factory()->create(['role' => 'admin']);
    $round = Round::factory()->f1()->create(['is_open' => false, 'is_locked' => true]);

    $this->actingAs($admin)
        ->post(route('admin.rounds.finalize', $round->slug))
        ->assertRedirect(route('admin.rounds.index'));

    Event::assertDispatched(RoundFinalized::class);
});

// --- pending-submissions: especiales pendientes (solo grupos) ---

it('pending endpoint includes users with missing special predictions for grupos round', function () {
    $round = Round::factory()->f1()->create(['is_open' => true]);

    $sinEspeciales  = User::factory()->activated()->create(['is_active' => true, 'name' => 'Sin Especiales']);
    $incompleto     = User::factory()->activated()->create(['is_active' => true, 'name' => 'Incompleto']);
    $completo       = User::factory()->activated()->create(['is_active' => true, 'name' => 'Completo']);

    $team   = \App\Models\Team::factory()->create();
    $player = \App\Models\Player::factory()->create();

    // Incompleto: tiene registro pero le falta el goleador
    \App\Models\SpecialPrediction::factory()->create([
        'user_id'              => $incompleto->id,
        'champion_team_id'     => $team->id,
        'runner_up_team_id'    => $team->id,
        'top_scorer_player_id' => null,
    ]);

    \App\Models\SpecialPrediction::factory()->create([
        'user_id'              => $completo->id,
        'champion_team_id'     => $team->id,
        'runner_up_team_id'    => $team->id,
        'top_scorer_player_id' => $player->id,
    ]);

    $response = $this->actingAs($this->admin)
        ->get(route('admin.rounds.pending', $round->slug))
        ->assertOk();

    $names = collect($response->json('pendingSpecials'))->pluck('name');
    expect($names)->toContain('Sin Especiales');
    expect($names)->toContain('Incompleto');
    expect($names)->not->toContain('Completo');
});

it('pending endpoint returns empty pendingSpecials for non-grupos rounds', function () {
    $round = Round::factory()->f2()->create(['is_open' => true]);

    User::factory()->activated()->create(['is_active' => true]);

    $response = $this->actingAs($this->admin)
        ->get(route('admin.rounds.pending', $round->slug))
        ->assertOk();

    expect($response->json('pendingSpecials'))->toBe([]);
});
