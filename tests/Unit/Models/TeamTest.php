<?php

use App\Models\Group;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('belongs to a group', function () {
    $group = Group::factory()->create();
    $team = Team::factory()->create(['group_id' => $group->id]);

    expect($team->group)->toBeInstanceOf(Group::class)
        ->and($team->group->id)->toBe($group->id);
});

it('has fifa_code', function () {
    $team = Team::factory()->create(['fifa_code' => 'ARG']);

    expect($team->fifa_code)->toBe('ARG');
});

it('has many players', function () {
    $team = Team::factory()->create();
    Player::factory()->count(3)->create(['team_id' => $team->id]);

    expect($team->players)->toHaveCount(3);
});
