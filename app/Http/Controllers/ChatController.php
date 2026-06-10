<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    public function index(): Response
    {
        $messages = Message::with('user:id,name,avatar')
            ->latest()
            ->limit(50)
            ->get()
            ->reverse()
            ->values();

        $liveMatch = \App\Models\Fixture::with(['homeTeam', 'awayTeam'])
            ->where('status', 'live')
            ->first();

        $liveMatchData = $liveMatch ? [
            'teamA'  => $liveMatch->homeTeam?->fifa_code ?? 'TBD',
            'teamB'  => $liveMatch->awayTeam?->fifa_code ?? 'TBD',
            'scoreA' => $liveMatch->home_score,
            'scoreB' => $liveMatch->away_score,
            'minute' => null,
        ] : null;

        return Inertia::render('Chat', [
            'messages'  => $messages,
            'liveMatch' => $liveMatchData,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'content' => ['required', 'string', 'max:500'],
        ]);

        $message = Message::create([
            'user_id' => $request->user()->id,
            'content' => $data['content'],
        ]);

        $message->load('user:id,name,avatar');

        // El mensaje ya está guardado: si Pusher falla o demora, no debe tumbar el request
        rescue(fn () => MessageSent::dispatch(
            $message->id,
            $request->user()->id,
            $request->user()->name,
            $request->user()->avatar,
            $data['content'],
            $message->created_at->toISOString(),
        ));

        return back();
    }
}
