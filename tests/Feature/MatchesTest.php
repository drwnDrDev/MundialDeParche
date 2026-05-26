<?php

use App\Models\User;
use App\Models\Round;
use App\Models\Group;
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
            ->has('currentRound')
        );
});

it('guest is redirected from matches page', function () {
    $this->get('/matches')->assertRedirect('/login');
});
