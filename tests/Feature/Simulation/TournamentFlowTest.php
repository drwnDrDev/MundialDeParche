<?php

use App\Events\ExactScoreAlert;
use App\Events\LiveScoreUpdated;
use App\Events\MatchScoreUpdated;
use App\Events\RankingUpdated;
use App\Events\RoundFinalized;
use App\Events\RoundLocked;
use App\Events\RoundOpened;
use App\Models\Fixture;
use App\Models\Group;
use App\Models\Player;
use App\Models\Prediction;
use App\Models\PredictionSubmission;
use App\Models\Round;
use App\Models\SpecialPrediction;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

// ── Helpers ───────────────────────────────────────────────────────────────────

function makeSimUsers(): array
{
    $admin = User::factory()->create(['role' => 'admin']);
    $user1 = User::factory()->create(['role' => 'user', 'is_active' => true, 'is_activated' => true]);
    $user2 = User::factory()->create(['role' => 'user', 'is_active' => true, 'is_activated' => true]);
    $user3 = User::factory()->create(['role' => 'user', 'is_active' => true, 'is_activated' => true]);
    $user4 = User::factory()->create(['role' => 'user', 'is_active' => true, 'is_activated' => true]);
    $user5 = User::factory()->create(['role' => 'user', 'is_active' => true, 'is_activated' => true]);
    return compact('admin', 'user1', 'user2', 'user3', 'user4', 'user5');
}

function makeGruposRoundWithFixtures(int $count = 2): array
{
    $round = Round::factory()->f1()->create(['is_open' => false, 'is_locked' => false]);
    $group = Group::factory()->create(['name' => 'A']);
    $fixtures = [];
    for ($i = 1; $i <= $count; $i++) {
        $home = Team::factory()->create(['group_id' => $group->id]);
        $away = Team::factory()->create(['group_id' => $group->id]);
        $fixtures[] = Fixture::factory()->create([
            'round_id'     => $round->id,
            'group_id'     => $group->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'match_number' => $i,
            'match_date'   => now()->addDays($i),
        ]);
    }
    return compact('round', 'fixtures', 'group');
}

// ── Apertura de ronda ─────────────────────────────────────────────────────────

it('users cannot predict before round is open', function () {
    ['admin' => $admin, 'user1' => $user1] = makeSimUsers();
    ['round' => $round, 'fixtures' => $fixtures] = makeGruposRoundWithFixtures(1);

    $this->actingAs($user1)
        ->post(route('predictions.save', $round), [
            'predictions' => [$fixtures[0]->id => ['predicted_home' => 2, 'predicted_away' => 1]],
        ])
        ->assertSessionHas('status');

    expect(Prediction::count())->toBe(0);
});

it('admin can open a round and users can then predict', function () {
    Event::fake([RoundOpened::class]);
    ['admin' => $admin, 'user1' => $user1] = makeSimUsers();
    ['round' => $round, 'fixtures' => $fixtures] = makeGruposRoundWithFixtures(1);

    // Admin abre la ronda
    $this->actingAs($admin)->post("/admin/rounds/{$round->slug}/open");
    expect($round->fresh()->is_open)->toBeTrue();
    Event::assertDispatched(RoundOpened::class);

    // user1 puede predecir
    $this->actingAs($user1)
        ->post(route('predictions.save', $round), [
            'predictions' => [$fixtures[0]->id => ['predicted_home' => 2, 'predicted_away' => 1]],
        ]);

    expect(Prediction::where('user_id', $user1->id)->count())->toBe(1);
});

// ── Bloqueo de ronda ──────────────────────────────────────────────────────────

it('admin can lock round and users cannot predict after lock', function () {
    Event::fake([RoundLocked::class]);
    ['admin' => $admin, 'user1' => $user1, 'user2' => $user2] = makeSimUsers();
    ['round' => $round, 'fixtures' => $fixtures] = makeGruposRoundWithFixtures(1);

    // Abrir y predecir
    $this->actingAs($admin)->post("/admin/rounds/{$round->slug}/open");
    $this->actingAs($user1)->post(route('predictions.save', $round), [
        'predictions' => [$fixtures[0]->id => ['predicted_home' => 2, 'predicted_away' => 1]],
    ]);

    // Admin bloquea
    $this->actingAs($admin)->post("/admin/rounds/{$round->slug}/lock");
    expect($round->fresh()->is_locked)->toBeTrue();
    Event::assertDispatched(RoundLocked::class);

    // user2 intenta predecir después del bloqueo
    $this->actingAs($user2)
        ->post(route('predictions.save', $round), [
            'predictions' => [$fixtures[0]->id => ['predicted_home' => 3, 'predicted_away' => 0]],
        ])
        ->assertSessionHas('status');

    expect(Prediction::where('user_id', $user2->id)->count())->toBe(0);
});

it('locking grupos round also locks all special predictions', function () {
    ['admin' => $admin, 'user1' => $user1, 'user2' => $user2] = makeSimUsers();
    ['round' => $round] = makeGruposRoundWithFixtures(1);

    SpecialPrediction::factory()->create(['user_id' => $user1->id, 'is_locked' => false]);
    SpecialPrediction::factory()->create(['user_id' => $user2->id, 'is_locked' => false]);

    $this->actingAs($admin)->post("/admin/rounds/{$round->slug}/open");
    $this->actingAs($admin)->post("/admin/rounds/{$round->slug}/lock");

    expect(SpecialPrediction::where('is_locked', false)->count())->toBe(0);
});

// ── Carga de marcadores y puntos ──────────────────────────────────────────────

it('points are calculated after admin enters scores via score entry', function () {
    Event::fake([LiveScoreUpdated::class, RankingUpdated::class, ExactScoreAlert::class]);
    ['admin' => $admin, 'user1' => $user1, 'user2' => $user2] = makeSimUsers();
    ['round' => $round, 'fixtures' => $fixtures] = makeGruposRoundWithFixtures(1);
    $fixture = $fixtures[0];

    // Flujo: abrir → predecir → bloquear
    $this->actingAs($admin)->post("/admin/rounds/{$round->slug}/open");

    $this->actingAs($user1)->post(route('predictions.save', $round), [
        'predictions' => [$fixture->id => ['predicted_home' => 2, 'predicted_away' => 1]],
    ]);
    // Mark submission as submitted so CalculateMatchPoints picks it up
    PredictionSubmission::updateOrCreate(
        ['user_id' => $user1->id, 'round_id' => $round->id],
        ['status' => 'submitted']
    );

    $this->actingAs($user2)->post(route('predictions.save', $round), [
        'predictions' => [$fixture->id => ['predicted_home' => 3, 'predicted_away' => 1]],
    ]);
    PredictionSubmission::updateOrCreate(
        ['user_id' => $user2->id, 'round_id' => $round->id],
        ['status' => 'submitted']
    );

    $this->actingAs($admin)->post("/admin/rounds/{$round->slug}/lock");

    // Admin carga marcador: 2-1 (exacto para user1, resultado correcto para user2)
    $this->actingAs($admin)->patch(route('admin.score-entry.update', $fixture), [
        'home_score'     => 2,
        'away_score'     => 1,
        'status'         => 'finished',
        'winner_team_id' => $fixture->home_team_id,
    ]);

    // user1 tiene pts_exact + pts_result (exacto 2-1 = home win también da resultado)
    $pred1 = Prediction::where('user_id', $user1->id)->where('match_id', $fixture->id)->first();
    expect($pred1->pts_exact)->toBe(3);
    expect($pred1->pts_result)->toBe(1);

    // user2 tiene solo pts_result (3-1 ≠ 2-1, pero ambos home win)
    $pred2 = Prediction::where('user_id', $user2->id)->where('match_id', $fixture->id)->first();
    expect($pred2->pts_exact)->toBe(0);
    expect($pred2->pts_result)->toBe(1);
});

it('user total_points reflects predictions after score entry', function () {
    Event::fake([LiveScoreUpdated::class, RankingUpdated::class, ExactScoreAlert::class]);
    ['admin' => $admin, 'user1' => $user1] = makeSimUsers();
    ['round' => $round, 'fixtures' => $fixtures] = makeGruposRoundWithFixtures(1);
    $fixture = $fixtures[0];

    $this->actingAs($admin)->post("/admin/rounds/{$round->slug}/open");
    $this->actingAs($user1)->post(route('predictions.save', $round), [
        'predictions' => [$fixture->id => ['predicted_home' => 2, 'predicted_away' => 1]],
    ]);
    PredictionSubmission::updateOrCreate(
        ['user_id' => $user1->id, 'round_id' => $round->id],
        ['status' => 'submitted']
    );
    $this->actingAs($admin)->post("/admin/rounds/{$round->slug}/lock");
    $this->actingAs($admin)->patch(route('admin.score-entry.update', $fixture), [
        'home_score'     => 2,
        'away_score'     => 1,
        'status'         => 'finished',
        'winner_team_id' => $fixture->home_team_id,
    ]);

    expect($user1->fresh()->total_points)->toBe(4);
});

// ── Finalización de ronda ─────────────────────────────────────────────────────

it('admin can finalize round after locking', function () {
    Event::fake([RoundFinalized::class, RoundLocked::class]);
    ['admin' => $admin] = makeSimUsers();
    ['round' => $round] = makeGruposRoundWithFixtures(1);

    $this->actingAs($admin)->post("/admin/rounds/{$round->slug}/open");
    $this->actingAs($admin)->post("/admin/rounds/{$round->slug}/lock");
    $this->actingAs($admin)->post("/admin/rounds/{$round->slug}/finalize");

    expect($round->fresh()->is_finalized)->toBeTrue();
    Event::assertDispatched(RoundFinalized::class);
});

it('admin cannot finalize an already finalized round', function () {
    ['admin' => $admin] = makeSimUsers();
    ['round' => $round] = makeGruposRoundWithFixtures(1);

    $this->actingAs($admin)->post("/admin/rounds/{$round->slug}/open");
    $this->actingAs($admin)->post("/admin/rounds/{$round->slug}/lock");
    $this->actingAs($admin)->post("/admin/rounds/{$round->slug}/finalize");
    $this->actingAs($admin)->post("/admin/rounds/{$round->slug}/finalize")
        ->assertSessionHas('status');

    expect(Round::where('id', $round->id)->where('is_finalized', true)->count())->toBe(1);
});

// ── Flujo multi-ronda ─────────────────────────────────────────────────────────

it('two consecutive rounds each score independently', function () {
    Event::fake([LiveScoreUpdated::class, RankingUpdated::class, ExactScoreAlert::class, RoundFinalized::class, RoundLocked::class]);
    ['admin' => $admin, 'user1' => $user1] = makeSimUsers();

    // R1
    $r1    = Round::factory()->f1()->create(['is_open' => false, 'is_locked' => false]);
    $group = Group::factory()->create(['name' => 'A']);
    $home  = Team::factory()->create(['group_id' => $group->id]);
    $away  = Team::factory()->create(['group_id' => $group->id]);
    $f1    = Fixture::factory()->create([
        'round_id' => $r1->id, 'group_id' => $group->id,
        'home_team_id' => $home->id, 'away_team_id' => $away->id, 'match_number' => 1,
    ]);

    // R2
    $r2 = Round::factory()->f2()->create(['is_open' => false, 'is_locked' => false]);
    $f2 = Fixture::factory()->create([
        'round_id' => $r2->id,
        'home_team_id' => $home->id, 'away_team_id' => $away->id, 'match_number' => 73,
    ]);

    // R1 flow
    $this->actingAs($admin)->post("/admin/rounds/{$r1->slug}/open");
    $this->actingAs($user1)->post(route('predictions.save', $r1), [
        'predictions' => [$f1->id => ['predicted_home' => 1, 'predicted_away' => 0]],
    ]);
    PredictionSubmission::updateOrCreate(
        ['user_id' => $user1->id, 'round_id' => $r1->id],
        ['status' => 'submitted']
    );
    $this->actingAs($admin)->post("/admin/rounds/{$r1->slug}/lock");
    $this->actingAs($admin)->patch(route('admin.score-entry.update', $f1), [
        'home_score' => 1, 'away_score' => 0, 'status' => 'finished',
        'winner_team_id' => $f1->home_team_id,
    ]);
    $this->actingAs($admin)->post("/admin/rounds/{$r1->slug}/finalize");

    // R2 flow
    $this->actingAs($admin)->post("/admin/rounds/{$r2->slug}/open");
    $this->actingAs($user1)->post(route('predictions.save', $r2), [
        'predictions' => [$f2->id => ['predicted_home' => 2, 'predicted_away' => 0]],
    ]);
    PredictionSubmission::updateOrCreate(
        ['user_id' => $user1->id, 'round_id' => $r2->id],
        ['status' => 'submitted']
    );
    $this->actingAs($admin)->post("/admin/rounds/{$r2->slug}/lock");
    $this->actingAs($admin)->patch(route('admin.score-entry.update', $f2), [
        'home_score' => 2, 'away_score' => 0, 'status' => 'finished',
        'winner_team_id' => $f2->home_team_id,
    ]);

    // R2 pts_exact = 5 (por ser R2), R1 pts_exact = 3 (por ser R1)
    $predR1 = Prediction::where('user_id', $user1->id)->where('match_id', $f1->id)->first();
    $predR2 = Prediction::where('user_id', $user1->id)->where('match_id', $f2->id)->first();

    // R1 group stage: pts_exact=3 + pts_result=1 = 4
    // R2 knockout: pts_exact=5 + pts_result=2 = 7 → total = 11
    expect($predR1->pts_exact)->toBe(3);
    expect($predR2->pts_exact)->toBe(5);
    expect($user1->fresh()->total_points)->toBe(11);
});

// ── Usuario sin predicciones tiene 0 puntos ───────────────────────────────────

it('user without predictions in R1 has zero total_points', function () {
    Event::fake([LiveScoreUpdated::class, RankingUpdated::class, ExactScoreAlert::class]);
    ['admin' => $admin, 'user1' => $user1, 'user3' => $user3] = makeSimUsers();
    ['round' => $round, 'fixtures' => $fixtures] = makeGruposRoundWithFixtures(1);
    $fixture = $fixtures[0];

    $this->actingAs($admin)->post("/admin/rounds/{$round->slug}/open");

    // user1 predice, user3 no predice
    $this->actingAs($user1)->post(route('predictions.save', $round), [
        'predictions' => [$fixture->id => ['predicted_home' => 2, 'predicted_away' => 1]],
    ]);
    PredictionSubmission::updateOrCreate(
        ['user_id' => $user1->id, 'round_id' => $round->id],
        ['status' => 'submitted']
    );

    $this->actingAs($admin)->post("/admin/rounds/{$round->slug}/lock");
    $this->actingAs($admin)->patch(route('admin.score-entry.update', $fixture), [
        'home_score'     => 2,
        'away_score'     => 1,
        'status'         => 'finished',
        'winner_team_id' => $fixture->home_team_id,
    ]);

    expect($user1->fresh()->total_points)->toBeGreaterThan(0);
    expect($user3->fresh()->total_points)->toBe(0);
});

// ── Ranking refleja orden correcto ────────────────────────────────────────────

it('final ranking reflects correct point order', function () {
    Event::fake([LiveScoreUpdated::class, RankingUpdated::class, ExactScoreAlert::class]);
    ['admin' => $admin, 'user1' => $user1, 'user2' => $user2] = makeSimUsers();
    ['round' => $round, 'fixtures' => $fixtures] = makeGruposRoundWithFixtures(1);
    $fixture = $fixtures[0];

    $this->actingAs($admin)->post("/admin/rounds/{$round->slug}/open");

    // user1 predice exacto (2-1), user2 predice resultado correcto (3-1)
    $this->actingAs($user1)->post(route('predictions.save', $round), [
        'predictions' => [$fixture->id => ['predicted_home' => 2, 'predicted_away' => 1]],
    ]);
    PredictionSubmission::updateOrCreate(
        ['user_id' => $user1->id, 'round_id' => $round->id],
        ['status' => 'submitted']
    );

    $this->actingAs($user2)->post(route('predictions.save', $round), [
        'predictions' => [$fixture->id => ['predicted_home' => 3, 'predicted_away' => 1]],
    ]);
    PredictionSubmission::updateOrCreate(
        ['user_id' => $user2->id, 'round_id' => $round->id],
        ['status' => 'submitted']
    );

    $this->actingAs($admin)->post("/admin/rounds/{$round->slug}/lock");
    $this->actingAs($admin)->patch(route('admin.score-entry.update', $fixture), [
        'home_score'     => 2,
        'away_score'     => 1,
        'status'         => 'finished',
        'winner_team_id' => $fixture->home_team_id,
    ]);

    // user1 (exacto): pts_exact=3 + pts_result=1 = 4
    // user2 (resultado): pts_exact=0 + pts_result=1 = 1
    $ranked = \App\Models\User::whereIn('id', [$user1->id, $user2->id])
        ->orderBy('total_points', 'desc')
        ->pluck('id')
        ->toArray();

    expect($ranked[0])->toBe($user1->id);
    expect($ranked[1])->toBe($user2->id);
});
