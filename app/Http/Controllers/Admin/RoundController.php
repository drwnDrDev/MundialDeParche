<?php

namespace App\Http\Controllers\Admin;

use App\Events\RoundFinalized;
use App\Events\RoundLocked;
use App\Events\RoundOpened;
use App\Http\Controllers\Controller;
use App\Models\PredictionSubmission;
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

        $activeUserIds = \App\Models\User::where('is_active', true)
            ->where('is_activated', true)
            ->pluck('id');

        $existingSubmissions = PredictionSubmission::where('round_id', $round->id)
            ->whereIn('user_id', $activeUserIds)
            ->get()
            ->keyBy('user_id');

        foreach ($activeUserIds as $userId) {
            if (! isset($existingSubmissions[$userId])) {
                PredictionSubmission::create([
                    'user_id'      => $userId,
                    'round_id'     => $round->id,
                    'status'       => 'submitted',
                    'submitted_at' => now(),
                ]);
            } elseif ($existingSubmissions[$userId]->status === 'draft') {
                $existingSubmissions[$userId]->update([
                    'status'       => 'submitted',
                    'submitted_at' => now(),
                ]);
            }
        }

        RoundLocked::dispatch($round->name);

        return back()->with('status', "Ronda '{$round->name}' bloqueada.");
    }

    public function pendingSubmissions(Round $round): \Illuminate\Http\JsonResponse
    {
        $activeUserIds = \App\Models\User::where('is_active', true)
            ->where('is_activated', true)
            ->pluck('id');

        $submittedUserIds = PredictionSubmission::where('round_id', $round->id)
            ->whereIn('status', ['submitted', 'locked'])
            ->pluck('user_id');

        $pending = \App\Models\User::whereIn('id', $activeUserIds)
            ->whereNotIn('id', $submittedUserIds)
            ->orderBy('name')
            ->select(['id', 'name'])
            ->get();

        return response()->json(['pending' => $pending]);
    }

    public function finalize(Round $round): RedirectResponse
    {
        if ($round->is_finalized) {
            return back()->with('status', "La ronda '{$round->name}' ya fue finalizada.");
        }

        if (! $round->is_locked) {
            $round->update(['is_open' => false, 'is_locked' => true]);

            if ($round->slug === 'grupos') {
                \App\Models\SpecialPrediction::query()->update(['is_locked' => true]);
            }

            RoundLocked::dispatch($round->name);
        }

        RoundFinalized::dispatch($round);

        $round->update(['is_finalized' => true]);

        return redirect()->route('admin.rounds.index')
            ->with('status', "Ronda '{$round->name}' finalizada. Puntos de clasificados calculados.");
    }
}
