<?php

namespace App\Http\Controllers\Admin;

use App\Events\RoundFinalized;
use App\Http\Controllers\Controller;
use App\Models\Round;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RoundController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Rounds/Index', [
            'rounds' => Round::orderBy('order')->get(),
        ]);
    }

    public function open(Round $round): RedirectResponse
    {
        if ($round->is_locked) {
            return back()->with('status', 'No se puede abrir una ronda bloqueada.');
        }

        $round->update(['is_open' => true]);

        return back()->with('status', "Ronda '{$round->name}' abierta.");
    }

    public function lock(Round $round): RedirectResponse
    {
        $round->update(['is_open' => false, 'is_locked' => true]);

        return back()->with('status', "Ronda '{$round->name}' bloqueada.");
    }

    public function finalize(Round $round): RedirectResponse
    {
        if ($round->is_locked) {
            return back()->with('status', 'Esta ronda ya está finalizada.');
        }

        $round->update(['is_open' => false, 'is_locked' => true]);

        RoundFinalized::dispatch($round);

        return back()->with('status', "Ronda '{$round->name}' finalizada.");
    }
}
