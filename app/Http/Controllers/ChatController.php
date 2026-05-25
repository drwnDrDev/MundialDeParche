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

        return Inertia::render('Chat', [
            'messages' => $messages,
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

        MessageSent::dispatch(
            $message->id,
            $request->user()->id,
            $request->user()->name,
            $request->user()->avatar,
            $data['content'],
            $message->created_at->toISOString(),
        );

        return back();
    }
}
