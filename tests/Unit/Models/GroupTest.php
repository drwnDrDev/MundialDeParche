<?php

use App\Models\Group;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('has a name', function () {
    $group = Group::factory()->create(['name' => 'A']);

    expect($group->name)->toBe('A');
});

it('has many teams', function () {
    $group = Group::factory()->create();
    Team::factory()->count(4)->create(['group_id' => $group->id]);

    expect($group->teams)->toHaveCount(4);
});
