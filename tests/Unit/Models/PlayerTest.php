<?php

use App\Models\Player;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('belongs to a team', function () {
    $team = Team::factory()->create();
    $player = Player::factory()->create(['team_id' => $team->id]);

    expect($player->team)->toBeInstanceOf(Team::class)
        ->and($player->team->id)->toBe($team->id);
});
