<?php
// tests/Feature/HomeAlertsTest.php

use App\Models\PredictionSubmission;
use App\Models\Round;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->activated()->create(['is_active' => true]);
});

// --- phaseAlert ---

it('phaseAlert is null when no round is open', function () {
    $response = $this->withoutVite()->actingAs($this->user)->get('/dashboard');

    $response->assertInertia(fn ($page) => $page->where('phaseAlert', null));
});

it('phaseAlert is null when open round was updated more than 24h ago', function () {
    $round = Round::factory()->create(['is_open' => true, 'order' => 1]);
    \DB::table('rounds')->where('id', $round->id)->update(['updated_at' => now()->subHours(25)]);

    $response = $this->withoutVite()->actingAs($this->user)->get('/dashboard');

    $response->assertInertia(fn ($page) => $page->where('phaseAlert', null));
});

it('phaseAlert is null when user already has submitted predictions for the round', function () {
    $round = Round::factory()->create(['is_open' => true, 'order' => 2]);

    PredictionSubmission::factory()->submitted()->create([
        'user_id'  => $this->user->id,
        'round_id' => $round->id,
    ]);

    $response = $this->withoutVite()->actingAs($this->user)->get('/dashboard');

    $response->assertInertia(fn ($page) => $page->where('phaseAlert', null));
});

it('phaseAlert is present when round opened recently and user has no submission', function () {
    Round::factory()->create(['is_open' => false, 'order' => 1, 'name' => 'Grupos']);
    Round::factory()->create(['is_open' => true, 'order' => 2, 'name' => 'R32+R16']);

    $response = $this->withoutVite()->actingAs($this->user)->get('/dashboard');

    $props = $response->original->getData()['page']['props'];
    expect($props['phaseAlert'])->not->toBeNull();
    expect($props['phaseAlert']['fromRound'])->toBe('Grupos');
    expect($props['phaseAlert']['toRound'])->toBe('R32+R16');
});

// --- deadlineAlert ---

it('deadlineAlert is null when no round has closes_at set', function () {
    Round::factory()->create(['is_open' => true, 'is_locked' => false, 'closes_at' => null]);

    $response = $this->withoutVite()->actingAs($this->user)->get('/dashboard');

    $response->assertInertia(fn ($page) => $page->where('deadlineAlert', null));
});

it('deadlineAlert is null when closes_at is more than 24h away', function () {
    Round::factory()->create([
        'is_open'    => true,
        'is_locked'  => false,
        'closes_at'  => now()->addHours(30),
    ]);

    $response = $this->withoutVite()->actingAs($this->user)->get('/dashboard');

    $response->assertInertia(fn ($page) => $page->where('deadlineAlert', null));
});

it('deadlineAlert is null when user has no draft submission', function () {
    Round::factory()->create([
        'is_open'   => true,
        'is_locked' => false,
        'closes_at' => now()->addHours(2),
    ]);

    $response = $this->withoutVite()->actingAs($this->user)->get('/dashboard');

    $response->assertInertia(fn ($page) => $page->where('deadlineAlert', null));
});

it('deadlineAlert is present when closes_at is within 24h and user has draft submission', function () {
    $round = Round::factory()->create([
        'is_open'   => true,
        'is_locked' => false,
        'closes_at' => now()->addHours(2),
        'name'      => 'Grupos',
    ]);

    PredictionSubmission::factory()->create([
        'user_id'  => $this->user->id,
        'round_id' => $round->id,
        'status'   => 'draft',
    ]);

    $response = $this->withoutVite()->actingAs($this->user)->get('/dashboard');

    $props = $response->original->getData()['page']['props'];
    expect($props['deadlineAlert'])->not->toBeNull();
    expect($props['deadlineAlert']['round'])->toBe('Grupos');
    expect($props['deadlineAlert']['hoursLeft'])->toBeLessThanOrEqual(2);
});
