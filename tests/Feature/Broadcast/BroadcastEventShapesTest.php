<?php

use App\Events\ExactScoreAlert;
use App\Events\LiveScoreUpdated;
use App\Events\MessageSent;
use App\Events\PointsUpdated;
use App\Events\RoundLocked;
use App\Events\RoundOpened;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

it('LiveScoreUpdated broadcasts to presence-quinela with correct payload', function () {
    $event = new LiveScoreUpdated(matchId: 7, homeScore: 2, awayScore: 1, isLive: true);

    expect($event)->toBeInstanceOf(ShouldBroadcast::class);

    $channels = $event->broadcastOn();
    expect($channels)->toHaveCount(1);
    expect($channels[0])->toBeInstanceOf(PresenceChannel::class);
    expect($channels[0]->name)->toBe('presence-quinela');

    expect($event->broadcastWith())->toBe([
        'match_id'   => 7,
        'home_score' => 2,
        'away_score' => 1,
        'is_live'    => true,
        'status'     => 'scheduled',
    ]);
});

it('PointsUpdated broadcasts to presence-quinela and private-user', function () {
    $event = new PointsUpdated(userId: 3, totalPoints: 42, position: 5);

    expect($event)->toBeInstanceOf(ShouldBroadcast::class);

    $channels = $event->broadcastOn();
    expect($channels)->toHaveCount(2);

    $channelClasses = array_map(fn($c) => get_class($c), $channels);
    expect($channelClasses)->toContain(PresenceChannel::class);
    expect($channelClasses)->toContain(PrivateChannel::class);

    $privateChannel = collect($channels)->first(fn($c) => $c instanceof PrivateChannel);
    expect($privateChannel->name)->toBe('private-user.3');

    expect($event->broadcastWith())->toBe([
        'user_id'      => 3,
        'total_points' => 42,
        'position'     => 5,
    ]);
});

it('RoundOpened broadcasts to presence-quinela with round name', function () {
    $event = new RoundOpened(roundName: 'Fase de Grupos');

    expect($event)->toBeInstanceOf(ShouldBroadcast::class);

    $channels = $event->broadcastOn();
    expect($channels[0])->toBeInstanceOf(PresenceChannel::class);
    expect($event->broadcastWith())->toBe(['round' => 'Fase de Grupos']);
});

it('RoundLocked broadcasts to presence-quinela with round name', function () {
    $event = new RoundLocked(roundName: 'R32-R16');

    expect($event)->toBeInstanceOf(ShouldBroadcast::class);
    expect($event->broadcastOn()[0])->toBeInstanceOf(PresenceChannel::class);
    expect($event->broadcastWith())->toBe(['round' => 'R32-R16']);
});

it('MessageSent broadcasts to presence-quinela with full message payload', function () {
    $event = new MessageSent(
        messageId: 1,
        userId: 5,
        userName: 'Juan',
        userAvatar: null,
        content: 'Hola a todos',
        createdAt: '2026-06-01T12:00:00.000000Z',
    );

    expect($event)->toBeInstanceOf(ShouldBroadcast::class);
    expect($event->broadcastOn()[0])->toBeInstanceOf(PresenceChannel::class);

    expect($event->broadcastWith())->toBe([
        'id'         => 1,
        'user_id'    => 5,
        'user_name'  => 'Juan',
        'user_avatar'=> null,
        'content'    => 'Hola a todos',
        'created_at' => '2026-06-01T12:00:00.000000Z',
    ]);
});

it('ExactScoreAlert broadcasts to presence-quinela with username and score', function () {
    $event = new ExactScoreAlert(userName: 'Pedro', matchId: 3, homeScore: 3, awayScore: 0);

    expect($event)->toBeInstanceOf(ShouldBroadcast::class);
    expect($event->broadcastOn()[0])->toBeInstanceOf(PresenceChannel::class);

    expect($event->broadcastWith())->toBe([
        'user_name'  => 'Pedro',
        'match_id'   => 3,
        'home_score' => 3,
        'away_score' => 0,
    ]);
});
