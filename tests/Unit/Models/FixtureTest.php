<?php

use App\Models\Fixture;
use App\Models\Group;
use App\Models\Round;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('belongs to a round', function () {
    $round = Round::factory()->create();
    $fixture = Fixture::factory()->create(['round_id' => $round->id]);

    expect($fixture->round)->toBeInstanceOf(Round::class)
        ->and($fixture->round->id)->toBe($round->id);
});

it('identifies group stage matches', function () {
    $group = Group::factory()->create();
    $fixture = Fixture::factory()->create(['group_id' => $group->id]);

    expect($fixture->isGroupStage())->toBeTrue();
});

it('identifies knockout matches', function () {
    $fixture = Fixture::factory()->create(['group_id' => null]);

    expect($fixture->isGroupStage())->toBeFalse();
});

it('detects live status', function () {
    $fixture = Fixture::factory()->live()->create();

    expect($fixture->isLive())->toBeTrue()
        ->and($fixture->isFinished())->toBeFalse();
});

it('detects finished status', function () {
    $fixture = Fixture::factory()->finished(2, 1)->create();

    expect($fixture->isFinished())->toBeTrue()
        ->and($fixture->home_score)->toBe(2)
        ->and($fixture->away_score)->toBe(1);
});

it('can have home and away teams', function () {
    $home = Team::factory()->create();
    $away = Team::factory()->create();
    $fixture = Fixture::factory()->create([
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
    ]);

    expect($fixture->homeTeam->id)->toBe($home->id)
        ->and($fixture->awayTeam->id)->toBe($away->id);
});

it('can have placeholder text for unknown teams', function () {
    $fixture = Fixture::factory()->create([
        'home_team_id' => null,
        'home_placeholder' => 'Ganador Grupo A',
    ]);

    expect($fixture->home_team_id)->toBeNull()
        ->and($fixture->home_placeholder)->toBe('Ganador Grupo A');
});
