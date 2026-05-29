<?php

use App\Models\Round;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
    $round = Round::factory()->f1()->create(['is_open' => true, 'is_locked' => false]);

    $this->actingAs($this->admin)->post("/admin/rounds/{$round->slug}/finalize");

    expect($round->fresh()->is_locked)->toBeTrue();
    expect($round->fresh()->is_open)->toBeFalse();
});

it('does not reopen a locked round', function () {
    $round = Round::factory()->f1()->create(['is_open' => false, 'is_locked' => true]);

    $this->actingAs($this->admin)->post("/admin/rounds/{$round->slug}/open")->assertRedirect();

    expect($round->fresh()->is_open)->toBeFalse();
});
