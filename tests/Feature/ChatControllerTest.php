<?php

use App\Events\MessageSent;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['is_active' => true]);
});

it('shows the chat page with last 50 messages in chronological order', function () {
    // Create 60 messages; only last 50 should appear
    Message::factory(60)->create(['user_id' => $this->user->id]);

    $response = $this->withoutVite()->actingAs($this->user)->get('/chat');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Chat')
        ->has('messages', 50)
    );
});

it('messages are returned in chronological order (oldest first)', function () {
    $old = Message::factory()->create(['user_id' => $this->user->id, 'created_at' => now()->subMinutes(5)]);
    $new = Message::factory()->create(['user_id' => $this->user->id, 'created_at' => now()]);

    $response = $this->withoutVite()->actingAs($this->user)->get('/chat');

    $messages = $response->original->getData()['page']['props']['messages'];
    expect($messages[0]['id'])->toBe($old->id);
    expect($messages[1]['id'])->toBe($new->id);
});

it('messages include user name and avatar', function () {
    Message::factory()->create(['user_id' => $this->user->id]);

    $response = $this->withoutVite()->actingAs($this->user)->get('/chat');

    $response->assertInertia(fn ($page) => $page
        ->has('messages.0.user.name')
        ->has('messages.0.user.avatar')
    );
});

it('stores a new message and dispatches MessageSent', function () {
    Event::fake([MessageSent::class]);

    $this->actingAs($this->user)->post('/chat/messages', [
        'content' => 'Vamos Argentina!',
    ])->assertRedirect();

    expect(Message::count())->toBe(1);
    expect(Message::first()->content)->toBe('Vamos Argentina!');
    expect(Message::first()->user_id)->toBe($this->user->id);

    Event::assertDispatched(MessageSent::class, function ($e) {
        return $e->userId === $this->user->id
            && $e->userName === $this->user->name
            && $e->content === 'Vamos Argentina!';
    });
});

it('stores the message and redirects even when the broadcaster fails', function () {
    \Illuminate\Support\Facades\Broadcast::extend('failing', function () {
        return new class implements \Illuminate\Contracts\Broadcasting\Broadcaster {
            public function auth($request) {}
            public function validAuthenticationResponse($request, $result) {}
            public function broadcast(array $channels, $event, array $payload = [])
            {
                throw new \Illuminate\Broadcasting\BroadcastException('Pusher timeout');
            }
        };
    });
    config([
        'broadcasting.default' => 'failing',
        'broadcasting.connections.failing' => ['driver' => 'failing'],
    ]);

    $this->actingAs($this->user)->post('/chat/messages', [
        'content' => 'Hola desde el partido',
    ])->assertRedirect();

    expect(Message::count())->toBe(1);
    expect(Message::first()->content)->toBe('Hola desde el partido');
});

it('rejects empty message content', function () {
    $this->actingAs($this->user)->post('/chat/messages', [
        'content' => '',
    ])->assertSessionHasErrors('content');

    expect(Message::count())->toBe(0);
});

it('rejects message content over 500 characters', function () {
    $this->actingAs($this->user)->post('/chat/messages', [
        'content' => str_repeat('a', 501),
    ])->assertSessionHasErrors('content');
});

it('guests cannot access chat', function () {
    $this->get('/chat')->assertRedirect('/login');
    $this->post('/chat/messages', ['content' => 'hi'])->assertRedirect('/login');
});
