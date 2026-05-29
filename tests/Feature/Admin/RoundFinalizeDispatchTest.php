<?php

use App\Events\RoundFinalized;
use App\Models\Round;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
});

it('dispatches RoundFinalized when admin finalizes a round', function () {
    Event::fake();
    $round = Round::factory()->f1()->create(['is_open' => true, 'is_locked' => false]);

    $this->actingAs($this->admin)->post("/admin/rounds/{$round->slug}/finalize");

    Event::assertDispatched(RoundFinalized::class, function ($event) use ($round) {
        return $event->round->id === $round->id;
    });
});

it('does not dispatch RoundFinalized a second time when round is already finalized', function () {
    Event::fake();
    $round = Round::factory()->f1()->create([
        'is_open'       => false,
        'is_locked'     => true,
        'is_finalized'  => true,
    ]);

    $this->actingAs($this->admin)
        ->post("/admin/rounds/{$round->slug}/finalize")
        ->assertRedirect();

    Event::assertNotDispatched(RoundFinalized::class);
});
