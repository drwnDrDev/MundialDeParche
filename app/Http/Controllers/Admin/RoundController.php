<?php

namespace App\Http\Controllers\Admin;

use App\Events\RoundFinalized;
use App\Events\RoundLocked;
use App\Events\RoundOpened;
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

        RoundOpened::dispatch($round->name);

        return back()->with('status', "Ronda '{$round->name}' abierta.");
    }

    public function lock(Round $round): RedirectResponse
    {
        $round->update(['is_open' => false, 'is_locked' => true]);

        if ($round->slug === 'grupos') {
            \App\Models\SpecialPrediction::query()->update(['is_locked' => true]);
        }

        RoundLocked::dispatch($round->name);

        return back()->with('status', "Ronda '{$round->name}' bloqueada.");
    }

    public function finalize(Round $round): RedirectResponse
    {
        if ($round->is_finalized) {
            return back()->with('status', "La ronda '{$round->name}' ya fue finalizada.");
        }

        if (! $round->is_locked) {
            $round->update(['is_open' => false, 'is_locked' => true]);
            RoundLocked::dispatch($round->name);
        }

        RoundFinalized::dispatch($round);

        $round->update(['is_finalized' => true]);

        return redirect()->route('admin.rounds.index')
            ->with('status', "Ronda '{$round->name}' finalizada. Puntos de clasificados calculados.");
    }
}
