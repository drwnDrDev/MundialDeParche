<?php

use App\Events\RoundFinalized;
use App\Events\RoundLocked;
use App\Events\RoundOpened;
use App\Models\Round;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
});

it('dispatches RoundOpened when admin opens a round', function () {
    Event::fake([RoundOpened::class]);
    $round = Round::factory()->f1()->create(['is_open' => false, 'is_locked' => false]);

    $this->actingAs($this->admin)->post("/admin/rounds/{$round->slug}/open");

    Event::assertDispatched(RoundOpened::class, fn ($e) => $e->roundName === $round->name);
});

it('does not dispatch RoundOpened when round is already locked', function () {
    Event::fake([RoundOpened::class]);
    $round = Round::factory()->f1()->create(['is_open' => false, 'is_locked' => true]);

    $this->actingAs($this->admin)->post("/admin/rounds/{$round->slug}/open");

    Event::assertNotDispatched(RoundOpened::class);
});

it('dispatches RoundLocked when admin locks a round', function () {
    Event::fake([RoundLocked::class]);
    $round = Round::factory()->f1()->create(['is_open' => true, 'is_locked' => false]);

    $this->actingAs($this->admin)->post("/admin/rounds/{$round->slug}/lock");

    Event::assertDispatched(RoundLocked::class, fn ($e) => $e->roundName === $round->name);
});

it('dispatches RoundLocked and RoundFinalized when admin finalizes a round', function () {
    Event::fake([RoundLocked::class, RoundFinalized::class]);
    $round = Round::factory()->f1()->create(['is_open' => true, 'is_locked' => false]);

    $this->actingAs($this->admin)->post("/admin/rounds/{$round->slug}/finalize");

    Event::assertDispatched(RoundLocked::class, fn ($e) => $e->roundName === $round->name);
    Event::assertDispatched(RoundFinalized::class, fn ($e) => $e->round->is($round));
});
