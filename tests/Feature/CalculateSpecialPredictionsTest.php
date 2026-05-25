<?php

use App\Events\TournamentFinalized;
use App\Listeners\CalculateSpecialPredictions;
use App\Models\Group;
use App\Models\Player;
use App\Models\SpecialPrediction;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('awards pts_champion when user correctly predicted the champion', function () {
    $group  = Group::factory()->create();
    $champ  = Team::factory()->create(['group_id' => $group->id]);
    $runner = Team::factory()->create(['group_id' => $group->id]);
    $scorer = Player::factory()->create();
    $user   = User::factory()->create();

    SpecialPrediction::factory()->create([
        'user_id'              => $user->id,
        'champion_team_id'     => $champ->id,
        'runner_up_team_id'    => $runner->id,
        'top_scorer_player_id' => $scorer->id,
    ]);

    (new CalculateSpecialPredictions)->handle(
        new TournamentFinalized($champ->id, $runner->id, $scorer->id)
    );

    $sp = SpecialPrediction::where('user_id', $user->id)->first();
    expect($sp->pts_champion)->toBe(30);
    expect($sp->pts_runner_up)->toBe(10);
    expect($sp->pts_top_scorer)->toBe(15);
    expect($sp->calculated_at)->not->toBeNull();
    expect($sp->is_locked)->toBeTrue();
});

it('awards zero pts for wrong special predictions', function () {
    $group   = Group::factory()->create();
    $champ   = Team::factory()->create(['group_id' => $group->id]);
    $runner  = Team::factory()->create(['group_id' => $group->id]);
    $wrong1  = Team::factory()->create(['group_id' => $group->id]);
    $wrong2  = Team::factory()->create(['group_id' => $group->id]);
    $scorer  = Player::factory()->create();
    $wrong3  = Player::factory()->create();
    $user    = User::factory()->create();

    SpecialPrediction::factory()->create([
        'user_id'              => $user->id,
        'champion_team_id'     => $wrong1->id,
        'runner_up_team_id'    => $wrong2->id,
        'top_scorer_player_id' => $wrong3->id,
    ]);

    (new CalculateSpecialPredictions)->handle(
        new TournamentFinalized($champ->id, $runner->id, $scorer->id)
    );

    $sp = SpecialPrediction::where('user_id', $user->id)->first();
    expect($sp->pts_champion)->toBe(0);
    expect($sp->pts_runner_up)->toBe(0);
    expect($sp->pts_top_scorer)->toBe(0);
});

it('updates user total_points after special prediction calculation', function () {
    $group  = Group::factory()->create();
    $champ  = Team::factory()->create(['group_id' => $group->id]);
    $runner = Team::factory()->create(['group_id' => $group->id]);
    $scorer = Player::factory()->create();
    $user   = User::factory()->create();

    SpecialPrediction::factory()->create([
        'user_id'              => $user->id,
        'champion_team_id'     => $champ->id,
        'runner_up_team_id'    => $runner->id,
        'top_scorer_player_id' => $scorer->id,
    ]);

    (new CalculateSpecialPredictions)->handle(
        new TournamentFinalized($champ->id, $runner->id, $scorer->id)
    );

    // 30 (champion) + 10 (runner_up) + 15 (top_scorer) = 55
    expect($user->fresh()->total_points)->toBe(55);
});

it('locks all special predictions after calculation', function () {
    $group  = Group::factory()->create();
    $champ  = Team::factory()->create(['group_id' => $group->id]);
    $runner = Team::factory()->create(['group_id' => $group->id]);
    $scorer = Player::factory()->create();

    $users = User::factory(3)->create();
    foreach ($users as $u) {
        SpecialPrediction::factory()->create([
            'user_id'              => $u->id,
            'champion_team_id'     => $champ->id,
            'runner_up_team_id'    => $runner->id,
            'top_scorer_player_id' => $scorer->id,
        ]);
    }

    (new CalculateSpecialPredictions)->handle(
        new TournamentFinalized($champ->id, $runner->id, $scorer->id)
    );

    expect(SpecialPrediction::where('is_locked', true)->count())->toBe(3);
});
